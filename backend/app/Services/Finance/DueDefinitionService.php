<?php

namespace App\Services\Finance;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DueDefinitionModel;
use Config\Database;

class DueDefinitionService extends BaseService
{
    public function __construct(private readonly DueDefinitionModel $model = new DueDefinitionModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'name', 'calculation_type', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'status', 'calculation_type'],
        ]);
        $builder = $this->model->builder()->select('*')->where('deleted_at', null);
        if (! array_key_exists('status', $q['filters'])) {
            $builder->where('status', 'active');
        }
        if ($q['search'] !== '') {
            $builder->groupStart()->like('name', $q['search'])->orLike('code', $q['search'])->groupEnd();
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy($q['sort'], $q['direction'])->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])->get()->getResultArray();
        return ListQuery::envelope($q['page'], $q['per_page'], $total, $items);
    }

    public function create(array $payload): array
    {
        $siteId = isset($payload['site_id']) ? (int) $payload['site_id'] : null;
        $blockId = isset($payload['block_id']) ? (int) $payload['block_id'] : null;
        $this->assertSiteBlockConsistency($siteId, $blockId);
        $data = [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'name' => trim((string) $payload['name']),
            'code' => isset($payload['code']) ? trim((string) $payload['code']) : null,
            'calculation_type' => (string) $payload['calculation_type'],
            'amount' => $payload['amount'],
            'currency' => (string) ($payload['currency'] ?? 'TRY'),
            'status' => (string) ($payload['status'] ?? 'active'),
        ];
        $this->model->insert($data, true);
        $id = (int) $this->model->getInsertID();
        $created = $this->show($id);
        $this->audit('finance.due_definition.create.success', ['entity_type' => 'due_definition', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessible($id);
        $row = $this->model->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Due definition bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $siteId = array_key_exists('site_id', $payload) ? (int) $payload['site_id'] : ((isset($current['site_id']) && $current['site_id'] !== null) ? (int) $current['site_id'] : null);
        $blockId = array_key_exists('block_id', $payload) ? (int) $payload['block_id'] : ((isset($current['block_id']) && $current['block_id'] !== null) ? (int) $current['block_id'] : null);
        $this->assertSiteBlockConsistency($siteId, $blockId);

        $data = [];
        foreach (['site_id', 'block_id', 'amount'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        foreach (['name', 'code', 'calculation_type', 'currency', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = trim((string) $payload[$field]);
            }
        }
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $updated = $this->show($id);
        $this->audit('finance.due_definition.update.success', ['entity_type' => 'due_definition', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->model->delete($id);
        $this->audit('finance.due_definition.delete.success', ['entity_type' => 'due_definition', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertSiteBlockConsistency(?int $siteId, ?int $blockId): void
    {
        if ($siteId !== null && $siteId > 0) {
            $site = Database::connect()->table('sites')->where('id', $siteId)->where('deleted_at', null)->get()->getRowArray();
            if (! is_array($site)) {
                throw new NotFoundApiException('Site bulunamadi');
            }
            $this->assertTenant((int) $site['company_id']);
        }
        if ($blockId !== null && $blockId > 0) {
            $block = Database::connect()->table('blocks')->where('id', $blockId)->where('deleted_at', null)->get()->getRowArray();
            if (! is_array($block)) {
                throw new NotFoundApiException('Block bulunamadi');
            }
            $this->assertTenant((int) $block['company_id']);
            if ($siteId !== null && $siteId > 0 && (int) $block['site_id'] !== $siteId) {
                throw new ConflictApiException('block.site_id ve site_id uyumsuz');
            }
        }
    }

    private function assertAccessible(int $id): void
    {
        $row = Database::connect()->table('due_definitions')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Due definition bulunamadi');
        }
        $this->assertTenant((int) $row['company_id']);
        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Due definition bulunamadi');
        }
    }

    private function assertTenant(int $companyId): void
    {
        $ctx = (int) (service('request')->company_id ?? 0);
        if ($ctx > 0 && $companyId !== $ctx) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }
    }
}
