<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\ServiceRequestModel;
use Config\Database;
use Throwable;

class ServiceRequestService extends BaseService
{
    public function __construct(private readonly ServiceRequestModel $model = new ServiceRequestModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'request_no', 'priority', 'status', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'unit_id', 'resident_profile_id', 'status', 'priority', 'assigned_to_user_id'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('request_no', $q['search'])->orLike('title', $q['search'])->groupEnd();
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
            throw new NotFoundApiException('Service request bulunamadi');
        }
        return $row;
    }

    public function create(array $payload): array
    {
        $this->assertRelations($payload);
        $baseData = [
            'site_id' => (int) $payload['site_id'],
            'block_id' => isset($payload['block_id']) ? (int) $payload['block_id'] : null,
            'unit_id' => isset($payload['unit_id']) ? (int) $payload['unit_id'] : null,
            'resident_profile_id' => isset($payload['resident_profile_id']) ? (int) $payload['resident_profile_id'] : null,
            'category_id' => isset($payload['category_id']) ? (int) $payload['category_id'] : null,
            'title' => trim((string) $payload['title']),
            'description' => trim((string) $payload['description']),
            'priority' => (string) ($payload['priority'] ?? 'normal'),
            'status' => 'open',
            'source' => (string) ($payload['source'] ?? 'panel'),
            'assigned_to_user_id' => null,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
        $inserted = false;
        $attempt = 0;
        while (! $inserted && $attempt < 3) {
            $attempt++;
            try {
                $this->model->insert(array_merge($baseData, ['request_no' => $this->generateRequestNo()]), true);
                $inserted = true;
            } catch (Throwable $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }
            }
        }
        if (! $inserted) {
            throw new ConflictApiException('request_no uretilemedi, tekrar deneyiniz');
        }
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.service_request.create.success', ['entity_type' => 'service_request', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $this->assertEditable((string) $current['status']);
        $merged = array_merge($current, $payload);
        $this->assertRelations($merged, true);
        $data = [];
        foreach (['site_id', 'block_id', 'unit_id', 'resident_profile_id', 'category_id', 'title', 'description', 'priority', 'source'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = in_array($field, ['title', 'description', 'priority', 'source'], true) ? trim((string) $payload[$field]) : $payload[$field];
            }
        }
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('operation.service_request.update.success', ['entity_type' => 'service_request', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function assign(int $id, int $assignedToUserId): array
    {
        $current = $this->show($id);
        $this->assertEditable((string) $current['status']);
        if (! in_array((string) $current['status'], ['open', 'assigned'], true)) {
            throw new ConflictApiException('Bu durumda assign islemi yapilamaz');
        }
        $this->assertUserAccessible($assignedToUserId);
        $firstResponseAt = $current['first_response_at'] ?: date('Y-m-d H:i:s');
        $this->model->update($id, [
            'assigned_to_user_id' => $assignedToUserId,
            'status' => 'assigned',
            'first_response_at' => $firstResponseAt,
        ]);
        $updated = $this->show($id);
        $this->audit('operation.service_request.assign.success', ['entity_type' => 'service_request', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function resolve(int $id): array
    {
        $current = $this->show($id);
        if (! in_array((string) $current['status'], ['assigned', 'in_progress'], true)) {
            throw new ConflictApiException('Sadece assigned/in_progress request resolve edilebilir');
        }
        $this->model->update($id, ['status' => 'resolved', 'resolved_at' => date('Y-m-d H:i:s')]);
        $updated = $this->show($id);
        $this->audit('operation.service_request.resolve.success', ['entity_type' => 'service_request', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function close(int $id): array
    {
        $current = $this->show($id);
        if ((string) $current['status'] !== 'resolved') {
            throw new ConflictApiException('Sadece resolved request close edilebilir');
        }
        $openWorkOrder = Database::connect()->table('work_orders')
            ->where('service_request_id', $id)
            ->where('deleted_at', null)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get(1)->getRowArray();
        if ($openWorkOrder !== null) {
            throw new ConflictApiException('Acik work_order varken request close edilemez');
        }
        $this->model->update($id, ['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
        $updated = $this->show($id);
        $this->audit('operation.service_request.close.success', ['entity_type' => 'service_request', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function cancel(int $id): array
    {
        $current = $this->show($id);
        if (! in_array((string) $current['status'], ['open', 'assigned', 'in_progress'], true)) {
            throw new ConflictApiException('Bu durumda cancel islemi yapilamaz');
        }
        $this->model->update($id, ['status' => 'cancelled']);
        $updated = $this->show($id);
        $this->audit('operation.service_request.cancel.success', ['entity_type' => 'service_request', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function assertCommentable(int $id): array
    {
        $request = $this->show($id);
        if (in_array((string) $request['status'], ['closed', 'cancelled'], true)) {
            throw new ConflictApiException('Closed/cancelled request icin yorum eklenemez');
        }
        return $request;
    }

    private function assertEditable(string $status): void
    {
        if (in_array($status, ['closed', 'cancelled'], true)) {
            throw new ConflictApiException('Closed/cancelled request guncellenemez');
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function assertRelations(array $payload, bool $isUpdate = false): void
    {
        $siteId = isset($payload['site_id']) ? (int) $payload['site_id'] : null;
        $blockId = isset($payload['block_id']) && $payload['block_id'] !== null ? (int) $payload['block_id'] : null;
        $unitId = isset($payload['unit_id']) && $payload['unit_id'] !== null ? (int) $payload['unit_id'] : null;
        $residentId = isset($payload['resident_profile_id']) && $payload['resident_profile_id'] !== null ? (int) $payload['resident_profile_id'] : null;
        $categoryId = isset($payload['category_id']) && $payload['category_id'] !== null ? (int) $payload['category_id'] : null;

        if (! $isUpdate || $siteId !== null) {
            $site = $this->fetchRequired('sites', $siteId, 'Site bulunamadi');
            $this->assertTenant((int) $site['company_id']);
        }
        if ($blockId !== null) {
            $block = $this->fetchRequired('blocks', $blockId, 'Block bulunamadi');
            $this->assertTenant((int) $block['company_id']);
            if ($siteId !== null && (int) $block['site_id'] !== $siteId) {
                throw new ConflictApiException('block site ile uyumsuz');
            }
        }
        if ($unitId !== null) {
            $unit = $this->fetchRequired('units', $unitId, 'Unit bulunamadi');
            $this->assertTenant((int) $unit['company_id']);
            if ($siteId !== null && (int) $unit['site_id'] !== $siteId) {
                throw new ConflictApiException('unit site ile uyumsuz');
            }
            if ($blockId !== null && (int) $unit['block_id'] !== $blockId) {
                throw new ConflictApiException('unit block ile uyumsuz');
            }
        }
        if ($categoryId !== null) {
            $category = $this->fetchRequired('request_categories', $categoryId, 'Category bulunamadi');
            $this->assertTenant((int) $category['company_id']);
        }
        if ($residentId !== null) {
            $resident = $this->fetchRequired('resident_profiles', $residentId, 'Resident bulunamadi');
            $this->assertTenant((int) $resident['company_id']);
            if ($unitId !== null && ((string) ($payload['source'] ?? 'panel') !== 'admin')) {
                $occupancy = Database::connect()->table('unit_occupancies')
                    ->where('resident_profile_id', $residentId)
                    ->where('unit_id', $unitId)
                    ->where('status', 'active')
                    ->where('deleted_at', null)
                    ->where('company_id', (int) $resident['company_id'])
                    ->get(1)->getRowArray();
                if ($occupancy === null) {
                    throw new ConflictApiException('resident_profile ile unit aktif occupancy iliskisi yok');
                }
            }
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function fetchRequired(string $table, ?int $id, string $errorMessage): array
    {
        if ($id === null || $id <= 0) {
            throw new NotFoundApiException($errorMessage);
        }
        $row = Database::connect()->table($table)->where('id', $id)->where('deleted_at', null)->get(1)->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException($errorMessage);
        }
        return $row;
    }

    private function assertUserAccessible(int $userId): void
    {
        $user = Database::connect()->table('users')->where('id', $userId)->where('deleted_at', null)->get(1)->getRowArray();
        if (! is_array($user)) {
            throw new NotFoundApiException('Atanacak kullanici bulunamadi');
        }
        $this->assertTenant((int) $user['company_id']);
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('service_requests')->where('id', $id)->get()->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Service request bulunamadi');
        }
        $this->assertTenant((int) $row['company_id']);
    }

    private function assertTenant(int $companyId): void
    {
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && $ctx !== $companyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }

    private function generateRequestNo(): string
    {
        return 'SR-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    private function isDuplicateKey(Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        return str_contains($msg, 'duplicate') || str_contains($msg, 'unique');
    }
}
