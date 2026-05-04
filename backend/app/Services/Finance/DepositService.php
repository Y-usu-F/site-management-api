<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DepositModel;
use App\Models\DepositTransactionModel;
use Config\Database;
use Throwable;

class DepositService extends BaseService
{
    public function __construct(
        private readonly DepositModel $depositModel = new DepositModel(),
        private readonly DepositTransactionModel $transactionModel = new DepositTransactionModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'deposit_no', 'initial_amount', 'balance_amount', 'status', 'created_at'],
            'filterable' => ['site_id', 'unit_id', 'resident_profile_id', 'status', 'currency'],
        ]);
        $builder = $this->depositModel->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('deposit_no', $q['search'])->orLike('notes', $q['search'])->groupEnd();
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $row = Database::connect()->table('deposits')->where('id', $id)->get(1)->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Deposit bulunamadi');
        }
        $this->assertTenantByCompanyId((int) $row['company_id']);
        return $row;
    }

    public function create(array $payload): array
    {
        $data = $this->normalizeCreate($payload);
        $this->assertReferences($data);
        $this->assertNoActiveDuplicate($data['unit_id'], $data['resident_profile_id']);
        $data['balance_amount'] = $data['initial_amount'];
        $data['status'] = 'active';
        $data['created_by'] = (int) (service('request')->user_id ?? 0) ?: null;
        $id = $this->insertWithDepositNoRetry($data);
        $created = $this->show($id);
        $this->audit('finance.deposit.create.success', ['entity_type' => 'deposit', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        if (in_array((string) $current['status'], ['cancelled', 'refunded', 'applied_to_debt'], true)) {
            throw new ConflictApiException('Terminal durumdaki deposit guncellenemez');
        }
        $data = $this->normalizeUpdate($payload, $current);
        $this->assertReferences($data);
        $this->assertNoActiveDuplicate($data['unit_id'], $data['resident_profile_id'], $id);
        $this->depositModel->update($id, [
            'site_id' => $data['site_id'],
            'unit_id' => $data['unit_id'],
            'resident_profile_id' => $data['resident_profile_id'],
            'notes' => $data['notes'],
        ]);
        $updated = $this->show($id);
        $this->audit('finance.deposit.update.success', ['entity_type' => 'deposit', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function receive(int $id, array $payload): array
    {
        $current = $this->show($id);
        if ((string) $current['status'] !== 'active') {
            throw new ConflictApiException('receive sadece active deposit icin calisir');
        }
        if (($current['received_at'] ?? null) !== null) {
            throw new ConflictApiException('Deposit zaten teslim alinmis');
        }
        $now = (string) ($payload['transaction_date'] ?? date('Y-m-d H:i:s'));
        $this->depositModel->update($id, ['received_at' => $now]);
        $this->createTransaction($id, 'receive', (float) $current['initial_amount'], [
            'description' => $payload['description'] ?? null,
            'transaction_date' => $now,
        ]);
        $updated = $this->show($id);
        $this->audit('finance.deposit.receive.success', ['entity_type' => 'deposit', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function refund(int $id, array $payload): array
    {
        $amount = round((float) $payload['amount'], 2);
        return $this->runMutationTransaction($id, function (array $current, \CodeIgniter\Database\BaseConnection $db) use ($id, $payload, $amount): array {
            $this->assertMutableForFinancialAction($current);
            if ($amount > (float) $current['balance_amount']) {
                throw new ConflictApiException('refund amount balance_amount degerini gecemez');
            }
            $newBalance = round((float) $current['balance_amount'] - $amount, 2);
            $status = $newBalance <= 0 ? 'refunded' : 'partially_refunded';
            $this->depositModel->update($id, ['balance_amount' => $newBalance, 'status' => $status, 'closed_at' => $newBalance <= 0 ? date('Y-m-d H:i:s') : null]);
            $this->createTransaction($id, 'refund', $amount, $payload);
            return $this->show($id);
        }, 'finance.deposit.refund.success');
    }

    public function deduct(int $id, array $payload): array
    {
        $amount = round((float) $payload['amount'], 2);
        return $this->runMutationTransaction($id, function (array $current) use ($id, $payload, $amount): array {
            $this->assertMutableForFinancialAction($current);
            if ($amount > (float) $current['balance_amount']) {
                throw new ConflictApiException('deduction amount balance_amount degerini gecemez');
            }
            $newBalance = round((float) $current['balance_amount'] - $amount, 2);
            $status = $newBalance <= 0 ? 'applied_to_debt' : (string) $current['status'];
            $this->depositModel->update($id, ['balance_amount' => $newBalance, 'status' => $status, 'closed_at' => $newBalance <= 0 ? date('Y-m-d H:i:s') : null]);
            $this->createTransaction($id, 'deduction', $amount, $payload);
            return $this->show($id);
        }, 'finance.deposit.deduct.success');
    }

    public function applyToDebt(int $id, array $payload): array
    {
        $amount = round((float) $payload['amount'], 2);
        $dueItemId = (int) $payload['due_item_id'];
        return $this->runMutationTransaction($id, function (array $current, \CodeIgniter\Database\BaseConnection $db) use ($id, $payload, $amount, $dueItemId): array {
            $this->assertMutableForFinancialAction($current);
            $dueItem = $db->query('SELECT * FROM due_items WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$dueItemId])->getRowArray();
            if (! is_array($dueItem)) {
                throw new NotFoundApiException('Due item bulunamadi');
            }
            $this->assertTenantByCompanyId((int) $dueItem['company_id']);
            if ((int) $dueItem['site_id'] !== (int) $current['site_id']) {
                throw new ConflictApiException('due_item.site deposit.site ile uyumsuz');
            }
            if ((int) $dueItem['unit_id'] !== (int) $current['unit_id']) {
                throw new ConflictApiException('due_item.unit deposit.unit ile uyumsuz');
            }
            if (! in_array((string) $dueItem['status'], ['unpaid', 'partial'], true)) {
                throw new ConflictApiException('due_item status unpaid/partial olmali');
            }
            $period = $db->query('SELECT status FROM due_periods WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [(int) $dueItem['due_period_id']])->getRowArray();
            if (! is_array($period)) {
                throw new NotFoundApiException('Due period bulunamadi');
            }
            if ((string) $period['status'] === 'locked') {
                throw new ConflictApiException('locked period due_item icin apply_to_debt yapilamaz');
            }
            $dueRemaining = round((float) $dueItem['remaining_amount'], 2);
            if ($amount > (float) $current['balance_amount']) {
                throw new ConflictApiException('apply amount balance_amount degerini gecemez');
            }
            if ($amount > $dueRemaining) {
                throw new ConflictApiException('apply amount due_item.remaining_amount degerini gecemez');
            }

            $newBalance = round((float) $current['balance_amount'] - $amount, 2);
            $newPaid = round((float) $dueItem['paid_amount'] + $amount, 2);
            $newDueRemaining = round((float) $dueItem['amount'] - $newPaid, 2);
            $newDueStatus = $newDueRemaining <= 0 ? 'paid' : 'partial';
            $newDepositStatus = $newBalance <= 0 ? 'applied_to_debt' : (string) $current['status'];

            $db->table('due_items')->where('id', $dueItemId)->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newDueRemaining,
                'status' => $newDueStatus,
            ]);
            $this->depositModel->update($id, ['balance_amount' => $newBalance, 'status' => $newDepositStatus, 'closed_at' => $newBalance <= 0 ? date('Y-m-d H:i:s') : null]);
            $this->createTransaction($id, 'apply_to_debt', $amount, $payload, $dueItemId);
            return $this->show($id);
        }, 'finance.deposit.apply_to_debt.success');
    }

    public function cancel(int $id, array $payload): array
    {
        return $this->runMutationTransaction($id, function (array $current) use ($id, $payload): array {
            if (in_array((string) $current['status'], ['cancelled', 'refunded', 'applied_to_debt'], true)) {
                throw new ConflictApiException('Bu deposit cancel edilemez');
            }
            $tx = $this->transactionModel->builder()
                ->select('transaction_type')
                ->where('deposit_id', $id)
                ->where('deleted_at', null)
                ->get()->getResultArray();
            $types = array_values(array_unique(array_map(static fn (array $row): string => (string) $row['transaction_type'], $tx)));
            if ($types !== [] && $types !== ['receive']) {
                throw new ConflictApiException('Cancel sadece hic transaction yoksa veya sadece receive varsa calisir');
            }
            $this->depositModel->update($id, ['balance_amount' => 0, 'status' => 'cancelled', 'closed_at' => date('Y-m-d H:i:s')]);
            $this->createTransaction($id, 'cancel', 0, $payload);
            return $this->show($id);
        }, 'finance.deposit.cancel.success');
    }

    private function createTransaction(int $depositId, string $type, float $amount, array $payload = [], ?int $dueItemId = null): void
    {
        $deposit = $this->show($depositId);
        $this->transactionModel->insert([
            'deposit_id' => $depositId,
            'transaction_type' => $type,
            'amount' => round($amount, 2),
            'currency' => (string) $deposit['currency'],
            'due_item_id' => $dueItemId,
            'payment_id' => isset($payload['payment_id']) ? (int) $payload['payment_id'] : null,
            'description' => isset($payload['description']) && $payload['description'] !== '' ? (string) $payload['description'] : null,
            'transaction_date' => (string) ($payload['transaction_date'] ?? date('Y-m-d H:i:s')),
            'created_by' => (int) (service('request')->user_id ?? 0) ?: null,
        ], true);
    }

    private function runMutationTransaction(int $id, callable $callback, string $auditEvent): array
    {
        $db = Database::connect();
        $db->transBegin();
        try {
            $current = $db->query('SELECT * FROM deposits WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$id])->getRowArray();
            if (! is_array($current)) {
                throw new NotFoundApiException('Deposit bulunamadi');
            }
            $this->assertTenantByCompanyId((int) $current['company_id']);
            $updated = $callback($current, $db);
            if ($db->transStatus() === false) {
                throw new ConflictApiException('Deposit islemi tamamlanamadi');
            }
            $db->transCommit();
            $this->audit($auditEvent, ['entity_type' => 'deposit', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
            return $updated;
        } catch (Throwable $e) {
            $db->transRollback();
            if ($e instanceof ConflictApiException || $e instanceof NotFoundApiException || $e instanceof TenantAccessDeniedException) {
                throw $e;
            }
            throw new ConflictApiException('Deposit islemi tamamlanamadi');
        }
    }

    private function assertMutableForFinancialAction(array $deposit): void
    {
        if (in_array((string) $deposit['status'], ['cancelled', 'refunded', 'applied_to_debt'], true)) {
            throw new ConflictApiException('Bu deposit durumunda islem yapilamaz');
        }
    }

    private function normalizeCreate(array $payload): array
    {
        return [
            'site_id' => (int) $payload['site_id'],
            'unit_id' => (int) $payload['unit_id'],
            'resident_profile_id' => (int) $payload['resident_profile_id'],
            'initial_amount' => round((float) $payload['initial_amount'], 2),
            'currency' => isset($payload['currency']) && $payload['currency'] !== '' ? (string) $payload['currency'] : 'TRY',
            'notes' => isset($payload['notes']) && $payload['notes'] !== '' ? (string) $payload['notes'] : null,
        ];
    }

    private function normalizeUpdate(array $payload, array $current): array
    {
        return [
            'site_id' => isset($payload['site_id']) ? (int) $payload['site_id'] : (int) $current['site_id'],
            'unit_id' => isset($payload['unit_id']) ? (int) $payload['unit_id'] : (int) $current['unit_id'],
            'resident_profile_id' => isset($payload['resident_profile_id']) ? (int) $payload['resident_profile_id'] : (int) $current['resident_profile_id'],
            'notes' => array_key_exists('notes', $payload) ? (($payload['notes'] ?? '') === '' ? null : (string) $payload['notes']) : ($current['notes'] ?? null),
        ];
    }

    private function assertReferences(array $data): void
    {
        $db = Database::connect();
        $site = $db->table('sites')->where('id', $data['site_id'])->where('deleted_at', null)->get(1)->getRowArray();
        if (! is_array($site)) {
            throw new NotFoundApiException('Site bulunamadi');
        }
        $this->assertTenantByCompanyId((int) $site['company_id']);

        $unit = $db->table('units')->where('id', $data['unit_id'])->where('deleted_at', null)->get(1)->getRowArray();
        if (! is_array($unit)) {
            throw new NotFoundApiException('Unit bulunamadi');
        }
        $this->assertTenantByCompanyId((int) $unit['company_id']);
        if ((int) $unit['site_id'] !== (int) $data['site_id']) {
            throw new ConflictApiException('unit_id verilen site ile uyumlu degil');
        }

        $resident = $db->table('resident_profiles')->where('id', $data['resident_profile_id'])->where('deleted_at', null)->get(1)->getRowArray();
        if (! is_array($resident)) {
            throw new NotFoundApiException('Resident bulunamadi');
        }
        $this->assertTenantByCompanyId((int) $resident['company_id']);

        $occupancy = $db->table('unit_occupancies')
            ->where('unit_id', $data['unit_id'])
            ->where('resident_profile_id', $data['resident_profile_id'])
            ->whereIn('status', ['active', 'passive'])
            ->where('deleted_at', null)
            ->get(1)->getRowArray();
        if (! is_array($occupancy)) {
            throw new ConflictApiException('resident_profile_id unit ile aktif/gecmis occupancy iliskisine sahip olmali');
        }
    }

    private function assertNoActiveDuplicate(int $unitId, int $residentId, ?int $exceptId = null): void
    {
        $builder = $this->depositModel->builder()
            ->select('id')
            ->where('unit_id', $unitId)
            ->where('resident_profile_id', $residentId)
            ->where('status', 'active')
            ->where('deleted_at', null);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni unit + resident icin aktif deposit duplicate olamaz');
        }
    }

    private function generateDepositNo(): string
    {
        return 'DEP-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    private function assertTenantByCompanyId(int $companyId): void
    {
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && $companyId !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function insertWithDepositNoRetry(array $data): int
    {
        for ($i = 0; $i < 5; $i++) {
            $data['deposit_no'] = $this->generateDepositNo();
            try {
                $this->depositModel->insert($data, true);
                return (int) $this->depositModel->getInsertID();
            } catch (Throwable $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }
            }
        }
        throw new ConflictApiException('deposit_no uretilemedi');
    }

    private function isDuplicateKey(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate') || str_contains($message, 'unique');
    }
}
