<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\WorkOrderModel;
use Config\Database;

class WorkOrderService extends BaseService
{
    public function __construct(
        private readonly WorkOrderModel $model = new WorkOrderModel(),
        private readonly ServiceRequestService $requestService = new ServiceRequestService()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'status', 'planned_start_at', 'created_at'],
            'filterable' => ['service_request_id', 'assigned_to_user_id', 'status'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
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
            throw new NotFoundApiException('Work order bulunamadi');
        }
        return $row;
    }

    public function create(array $payload): array
    {
        $request = $this->requestService->show((int) $payload['service_request_id']);
        if (isset($payload['assigned_to_user_id'])) {
            $this->assertUserAccessible((int) $payload['assigned_to_user_id']);
        }
        $this->model->insert([
            'service_request_id' => (int) $request['id'],
            'assigned_to_user_id' => isset($payload['assigned_to_user_id']) ? (int) $payload['assigned_to_user_id'] : null,
            'vendor_name' => isset($payload['vendor_name']) ? trim((string) $payload['vendor_name']) : null,
            'status' => 'open',
            'planned_start_at' => $payload['planned_start_at'] ?? null,
            'planned_end_at' => $payload['planned_end_at'] ?? null,
            'cost_amount' => isset($payload['cost_amount']) ? (float) $payload['cost_amount'] : null,
            'currency' => (string) ($payload['currency'] ?? 'TRY'),
            'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) : null,
        ], true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.work_order.create.success', ['entity_type' => 'work_order', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $this->assertUpdatable((string) $current['status']);
        if (isset($payload['assigned_to_user_id'])) {
            $this->assertUserAccessible((int) $payload['assigned_to_user_id']);
        }
        $data = [];
        foreach (['assigned_to_user_id', 'planned_start_at', 'planned_end_at', 'cost_amount'] as $f) {
            if (array_key_exists($f, $payload)) {
                $data[$f] = $payload[$f];
            }
        }
        foreach (['vendor_name', 'currency', 'notes'] as $f) {
            if (array_key_exists($f, $payload)) {
                $data[$f] = $payload[$f] === null ? null : trim((string) $payload[$f]);
            }
        }
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('operation.work_order.update.success', ['entity_type' => 'work_order', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function start(int $id): array
    {
        $current = $this->show($id);
        if ((string) $current['status'] !== 'open') {
            throw new ConflictApiException('Bu work order start edilemez');
        }
        $this->model->update($id, ['status' => 'in_progress', 'started_at' => $current['started_at'] ?: date('Y-m-d H:i:s')]);
        $updated = $this->show($id);
        $this->audit('operation.work_order.start.success', ['entity_type' => 'work_order', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function complete(int $id): array
    {
        $current = $this->show($id);
        if ((string) $current['status'] !== 'in_progress') {
            throw new ConflictApiException('Sadece in_progress work order complete edilebilir');
        }
        $this->model->update($id, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        $updated = $this->show($id);
        $this->audit('operation.work_order.complete.success', ['entity_type' => 'work_order', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function cancel(int $id): array
    {
        $current = $this->show($id);
        if (in_array((string) $current['status'], ['completed', 'cancelled'], true)) {
            throw new ConflictApiException('Bu work order cancel edilemez');
        }
        $this->model->update($id, ['status' => 'cancelled']);
        $updated = $this->show($id);
        $this->audit('operation.work_order.cancel.success', ['entity_type' => 'work_order', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    private function assertUpdatable(string $status): void
    {
        if (in_array($status, ['completed', 'cancelled'], true)) {
            throw new ConflictApiException('completed/cancelled work_order guncellenemez');
        }
    }

    private function assertUserAccessible(int $userId): void
    {
        $user = Database::connect()->table('users')->where('id', $userId)->where('deleted_at', null)->get(1)->getRowArray();
        if (! is_array($user)) {
            throw new NotFoundApiException('User bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $user['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('work_orders')->where('id', $id)->get()->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Work order bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
