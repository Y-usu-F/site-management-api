<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DueItemModel;
use App\Models\PaymentAllocationModel;
use App\Models\PaymentModel;
use Config\Database;
use Throwable;

class PaymentService extends BaseService
{
    public function __construct(
        private readonly PaymentModel $paymentModel = new PaymentModel(),
        private readonly PaymentAllocationModel $allocationModel = new PaymentAllocationModel(),
        private readonly DueItemModel $dueItemModel = new DueItemModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $ctxCompanyId = (int) (service('request')->company_id ?? 0);
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'payment_date', 'amount', 'status', 'created_at'],
            'filterable' => ['site_id', 'unit_id', 'resident_profile_id', 'status', 'provider', 'method'],
        ]);
        $builder = $this->paymentModel->builder()->select('*')->where('deleted_at', null);
        if ($ctxCompanyId > 0) {
            $builder->where('company_id', $ctxCompanyId);
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('payment_no', $q['search'])->orLike('provider_reference', $q['search'])->groupEnd();
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $this->assertAccessiblePayment($id);
        $row = $this->paymentModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Payment bulunamadi');
        }
        return $row;
    }

    public function createManual(array $payload): array
    {
        $siteId = (int) $payload['site_id'];
        $unitId = isset($payload['unit_id']) ? (int) $payload['unit_id'] : null;
        $residentId = isset($payload['resident_profile_id']) ? (int) $payload['resident_profile_id'] : null;
        $idempotencyKey = isset($payload['idempotency_key']) ? trim((string) $payload['idempotency_key']) : null;
        $amount = (float) $payload['amount'];
        $currency = (string) ($payload['currency'] ?? 'TRY');
        $paymentDate = (string) ($payload['payment_date'] ?? date('Y-m-d H:i:s'));

        $this->assertSiteAccessible($siteId);
        if ($unitId !== null) {
            $this->assertUnitAccessible($unitId, $siteId);
        }
        if ($residentId !== null) {
            $this->assertResidentAccessible($residentId);
        }
        if ($unitId !== null && $residentId !== null) {
            $this->assertResidentHasActiveOccupancyInUnit($residentId, $unitId);
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = $this->paymentModel->builder()
                ->where('idempotency_key', $idempotencyKey)
                ->where('deleted_at', null)
                ->get(1)->getRowArray();
            if ($existing !== null) {
                return $this->show((int) $existing['id']);
            }
        }

        $remaining = $amount;
        $allocatedTotal = 0.0;
        $paymentNo = $this->generatePaymentNo();

        $db = Database::connect();
        $db->transStart();
        $paymentId = null;
        try {
            $this->paymentModel->insert([
                'site_id' => $siteId,
                'unit_id' => $unitId,
                'resident_profile_id' => $residentId,
                'payment_no' => $paymentNo,
                'provider' => 'manual',
                'provider_reference' => null,
                'idempotency_key' => $idempotencyKey,
                'amount' => $amount,
                'allocated_amount' => 0,
                'currency' => $currency,
                'payment_date' => $paymentDate,
                'status' => 'completed',
                'method' => (string) $payload['method'],
                'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
            ], true);
            $paymentId = (int) $this->paymentModel->getInsertID();

            $dueItems = $this->resolveDueItemsForAllocation($siteId, $unitId, $residentId, $currency);
            foreach ($dueItems as $item) {
                if ($remaining <= 0) {
                    break;
                }
                $itemRemaining = (float) $item['remaining_amount'];
                if ($itemRemaining <= 0) {
                    continue;
                }

                $allocate = min($remaining, $itemRemaining);
                if ($allocate <= 0) {
                    continue;
                }

                $this->allocationModel->insert([
                    'payment_id' => $paymentId,
                    'due_item_id' => (int) $item['id'],
                    'amount' => round($allocate, 2),
                ], true);
                $this->audit('finance.payment.allocation.create.success', [
                    'entity_type' => 'payment_allocation',
                    'entity_id' => (int) $this->allocationModel->getInsertID(),
                    'new_values' => ['payment_id' => $paymentId, 'due_item_id' => (int) $item['id'], 'amount' => round($allocate, 2)],
                ]);

                $fresh = $this->dueItemModel->builder()->select('amount,paid_amount,remaining_amount')
                    ->where('id', (int) $item['id'])
                    ->get(1)->getRowArray();
                $currentPaid = (float) ($fresh['paid_amount'] ?? $item['paid_amount']);
                $newPaid = round($currentPaid + $allocate, 2);
                $newRemaining = round((float) $item['amount'] - $newPaid, 2);
                $newStatus = $this->resolveDueItemStatus($newPaid, (float) $item['amount']);

                $this->dueItemModel->update((int) $item['id'], [
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'status' => $newStatus,
                ]);

                $allocatedTotal += $allocate;
                $remaining -= $allocate;
            }

            $this->paymentModel->update($paymentId, ['allocated_amount' => round($allocatedTotal, 2)]);
        } catch (Throwable $e) {
            if ($idempotencyKey !== null && $idempotencyKey !== '' && $this->isDuplicateKey($e)) {
                $db->transRollback();
                $existing = $this->paymentModel->builder()
                    ->where('idempotency_key', $idempotencyKey)
                    ->where('deleted_at', null)
                    ->get(1)->getRowArray();
                if ($existing !== null) {
                    return $this->show((int) $existing['id']);
                }
            }
            $db->transRollback();
            $this->audit('finance.payment.manual.create.failed', ['entity_type' => 'payment', 'entity_id' => $paymentId, 'meta' => ['error' => $e->getMessage()]]);
            throw new ConflictApiException('Manual payment olusturulamadi');
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new ConflictApiException('Manual payment transaction basarisiz');
        }

        $created = $this->show((int) $paymentId);
        $this->audit('finance.payment.manual.create.success', ['entity_type' => 'payment', 'entity_id' => $paymentId, 'new_values' => $created]);
        return $created;
    }

    public function cancel(int $id): array
    {
        $current = $this->show($id);
        $status = (string) $current['status'];
        if (in_array($status, ['completed', 'cancelled', 'refunded'], true)) {
            throw new ConflictApiException('Bu payment cancel edilemez');
        }
        $this->paymentModel->update($id, ['status' => 'cancelled']);
        $updated = $this->show($id);
        $this->audit('finance.payment.cancel.success', ['entity_type' => 'payment', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function resolveDueItemsForAllocation(int $siteId, ?int $unitId, ?int $residentId, string $currency): array
    {
        $ctxCompanyId = (int) (service('request')->company_id ?? 0);
        $sql = "SELECT due_items.* FROM due_items
            INNER JOIN due_periods ON due_periods.id = due_items.due_period_id
            WHERE due_items.site_id = ?
              AND due_items.deleted_at IS NULL
              AND due_items.status IN ('unpaid','partial')
              AND due_items.currency = ?
              AND due_periods.status != 'locked'";
        $params = [$siteId, $currency];

        if ($unitId !== null) {
            $sql .= " AND due_items.unit_id = ?";
            $params[] = $unitId;
        } elseif ($residentId !== null) {
            $unitIds = Database::connect()->table('unit_occupancies')
                ->select('unit_id')
                ->where('resident_profile_id', $residentId)
                ->where('status', 'active')
                ->where('deleted_at', null)
                ->where('company_id', $ctxCompanyId)
                ->get()->getResultArray();
            $ids = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['unit_id'], $unitIds)));
            if ($ids === []) {
                return [];
            }
            $sql .= " AND due_items.unit_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
            $params = array_merge($params, $ids);
        }
        if ($ctxCompanyId > 0) {
            $sql .= " AND due_items.company_id = ?";
            $params[] = $ctxCompanyId;
        }
        $sql .= " ORDER BY due_items.due_date ASC, due_items.id ASC FOR UPDATE";
        return Database::connect()->query($sql, $params)->getResultArray();
    }

    private function resolveDueItemStatus(float $paidAmount, float $amount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }
        if ($paidAmount >= $amount) {
            return 'paid';
        }
        return 'partial';
    }

    private function generatePaymentNo(): string
    {
        return 'PAY-' . date('YmdHis') . '-' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
    }

    private function assertAccessiblePayment(int $id): void
    {
        $row = Database::connect()->table('payments')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Payment bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Payment bulunamadi');
        }
    }

    private function assertSiteAccessible(int $siteId): void
    {
        $site = Database::connect()->table('sites')->where('id', $siteId)->where('deleted_at', null)->get()->getRowArray();
        if (! is_array($site)) {
            throw new NotFoundApiException('Site bulunamadi');
        }
        $this->assertTenant((int) $site['company_id']);
    }

    private function assertUnitAccessible(int $unitId, int $siteId): void
    {
        $unit = Database::connect()->table('units')->where('id', $unitId)->where('deleted_at', null)->get()->getRowArray();
        if (! is_array($unit)) {
            throw new NotFoundApiException('Unit bulunamadi');
        }
        $this->assertTenant((int) $unit['company_id']);
        if ((int) $unit['site_id'] !== $siteId) {
            throw new ConflictApiException('unit_id verilen site ile uyumlu degil');
        }
    }

    private function assertResidentAccessible(int $residentId): void
    {
        $resident = Database::connect()->table('resident_profiles')->where('id', $residentId)->where('deleted_at', null)->get()->getRowArray();
        if (! is_array($resident)) {
            throw new NotFoundApiException('Resident bulunamadi');
        }
        $this->assertTenant((int) $resident['company_id']);
    }

    private function assertResidentHasActiveOccupancyInUnit(int $residentId, int $unitId): void
    {
        $row = Database::connect()->table('unit_occupancies')
            ->where('resident_profile_id', $residentId)
            ->where('unit_id', $unitId)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->get(1)->getRowArray();
        if (! is_array($row)) {
            throw new ConflictApiException('resident_profile_id ve unit_id aktif iliski icermiyor');
        }
    }

    private function assertTenant(int $companyId): void
    {
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && $ctx !== $companyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function isDuplicateKey(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate') || str_contains($message, 'unique');
    }
}
