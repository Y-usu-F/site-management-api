<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\BlockModel;
use App\Models\FloorModel;
use Config\Database;

class FloorService extends BaseService
{
    public function __construct(
        private readonly FloorModel $floorModel = new FloorModel(),
        private readonly BlockModel $blockModel = new BlockModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'site_id', 'block_id', 'number', 'sort_order', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'status'],
        ]);

        $builder = $this->floorModel->builder()->select('*');
        if ($q['search'] !== '') {
            $builder->groupStart()->like('label', $q['search'])->orLike('number', $q['search'])->groupEnd();
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }

        $total = (int) $builder->countAllResults(false);
        $rows = $builder->orderBy($q['sort'], $q['direction'])
            ->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])
            ->get()->getResultArray();

        return ListQuery::envelope($q['page'], $q['per_page'], $total, $rows);
    }

    public function create(array $payload): array
    {
        $block = $this->assertBlockExists((int) $payload['block_id']);
        $siteId = (int) $payload['site_id'];
        if ((int) $block['site_id'] !== $siteId) {
            throw new ConflictApiException('Kat ile blok/site iliskisi tutarsiz');
        }

        $data = [
            'site_id' => $siteId,
            'block_id' => (int) $payload['block_id'],
            'number' => (int) $payload['number'],
            'label' => isset($payload['label']) ? trim((string) $payload['label']) : null,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        try {
            $this->floorModel->insert($data, true);
        } catch (\Throwable $e) {
            throw new ConflictApiException('Ayni blokta kat numarasi benzersiz olmali');
        }

        $id = (int) $this->floorModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.floor.create.success', ['entity_type' => 'floor', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleFloor($id);
        $row = $this->floorModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Kat bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = trim((string) $payload[$field]);
            }
        }
        foreach (['site_id', 'block_id', 'number', 'sort_order'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = (int) $payload[$field];
            }
        }
        if (array_key_exists('label', $payload)) {
            $data['label'] = trim((string) $payload['label']);
        }

        $nextSiteId = (int) ($data['site_id'] ?? $current['site_id']);
        $nextBlockId = (int) ($data['block_id'] ?? $current['block_id']);
        $block = $this->assertBlockExists($nextBlockId);
        if ((int) $block['site_id'] !== $nextSiteId) {
            throw new ConflictApiException('Kat ile blok/site iliskisi tutarsiz');
        }

        if ($data !== []) {
            try {
                $this->floorModel->update($id, $data);
            } catch (\Throwable $e) {
                throw new ConflictApiException('Ayni blokta kat numarasi benzersiz olmali');
            }
        }

        $updated = $this->show($id);
        $this->audit('site.floor.update.success', ['entity_type' => 'floor', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->floorModel->delete($id);
        $this->audit('site.floor.delete.success', ['entity_type' => 'floor', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertBlockExists(int $blockId): array
    {
        $block = $this->blockModel->tenantFind($blockId);
        if (! is_array($block)) {
            throw new NotFoundApiException('Ilgili blok bulunamadi');
        }
        return $block;
    }

    private function assertAccessibleFloor(int $id): void
    {
        $row = Database::connect()->table('floors')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Kat bulunamadi');
        }

        $request = service('request');
        $contextCompanyId = (int) ($request->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Kat bulunamadi');
        }
    }
}
