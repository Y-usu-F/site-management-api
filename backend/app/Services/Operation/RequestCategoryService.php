<?php

namespace App\Services\Operation;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\RequestCategoryModel;
use Config\Database;

class RequestCategoryService extends BaseService
{
    public function __construct(private readonly RequestCategoryModel $model = new RequestCategoryModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'name', 'status', 'created_at'],
            'filterable' => ['status'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        foreach ($q['filters'] as $f => $v) {
            $builder->where($f, $v);
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('name', $q['search'])->orLike('code', $q['search'])->groupEnd();
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
            throw new NotFoundApiException('Request category bulunamadi');
        }
        return $row;
    }

    public function create(array $payload): array
    {
        $this->model->insert([
            'name' => trim((string) $payload['name']),
            'code' => isset($payload['code']) ? trim((string) $payload['code']) : null,
            'status' => (string) ($payload['status'] ?? 'active'),
        ], true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('operation.request_category.create.success', ['entity_type' => 'request_category', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['name', 'code', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = is_string($payload[$field]) ? trim($payload[$field]) : $payload[$field];
            }
        }
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('operation.request_category.update.success', ['entity_type' => 'request_category', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->model->delete($id);
        $this->audit('operation.request_category.delete.success', ['entity_type' => 'request_category', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('request_categories')->where('id', $id)->get()->getRowArray();
        if (! is_array($row) || ($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Request category bulunamadi');
        }
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && (int) $row['company_id'] !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
