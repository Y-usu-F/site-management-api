<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DueItemModel;
use Config\Database;

class DueItemService extends BaseService
{
    public function __construct(private readonly DueItemModel $model = new DueItemModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'due_date', 'amount', 'remaining_amount', 'created_at'],
            'filterable' => ['site_id', 'unit_id', 'due_period_id', 'status'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        if (! array_key_exists('status', $q['filters'])) {
            $builder->whereIn('status', ['unpaid', 'partial', 'paid']);
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Due item bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $this->assertPeriodNotLocked((int) $current['due_period_id']);
        if ((string) $current['status'] === 'cancelled') {
            throw new ConflictApiException('Cancel edilmis due_item guncellenemez');
        }
        $paidAmount = array_key_exists('paid_amount', $payload) ? (float) $payload['paid_amount'] : (float) $current['paid_amount'];
        $amount = (float) $current['amount'];
        if ($paidAmount > $amount) {
            throw new ConflictApiException('paid_amount amount degerini gecemez');
        }
        $remaining = round($amount - $paidAmount, 2);
        $status = $this->resolvePaymentStatus($paidAmount, $amount);
        $data = [
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remaining,
            'status' => $status,
        ];
        if (array_key_exists('description', $payload)) {
            $data['description'] = trim((string) $payload['description']);
        }
        $this->model->update($id, $data);
        $updated = $this->show($id);
        $this->audit('finance.due_item.update.success', ['entity_type' => 'due_item', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function cancel(int $id): array
    {
        $current = $this->show($id);
        $this->assertPeriodNotLocked((int) $current['due_period_id']);
        if ((string) $current['status'] === 'cancelled') {
            throw new ConflictApiException('Due item zaten cancelled');
        }
        $this->model->update($id, ['status' => 'cancelled']);
        $updated = $this->show($id);
        $this->audit('finance.due_item.cancel.success', ['entity_type' => 'due_item', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('due_items')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Due item bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Due item bulunamadi');
        }
    }

    private function resolvePaymentStatus(float $paidAmount, float $amount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }
        if ($paidAmount >= $amount) {
            return 'paid';
        }
        return 'partial';
    }

    private function assertPeriodNotLocked(int $duePeriodId): void
    {
        $period = Database::connect()->table('due_periods')
            ->select('status, deleted_at')
            ->where('id', $duePeriodId)
            ->get(1)
            ->getRowArray();
        if (! is_array($period) || ($period['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Due period bulunamadi');
        }
        if ((string) ($period['status'] ?? '') === 'locked') {
            throw new ConflictApiException('locked due_period uzerinde finansal degisiklik yapilamaz');
        }
    }
}
