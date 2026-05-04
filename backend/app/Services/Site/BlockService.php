<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\BlockModel;
use App\Models\SiteModel;
use Config\Database;

class BlockService extends BaseService
{
    public function __construct(
        private readonly BlockModel $blockModel = new BlockModel(),
        private readonly SiteModel $siteModel = new SiteModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'name', 'code', 'site_id', 'sort_order', 'created_at'],
            'filterable' => ['site_id', 'status'],
        ]);

        $builder = $this->blockModel->builder()->select('*');
        if ($q['search'] !== '') {
            $builder->groupStart()->like('name', $q['search'])->orLike('code', $q['search'])->groupEnd();
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
        $siteId = (int) $payload['site_id'];
        $this->assertSiteExists($siteId);
        $data = [
            'site_id' => $siteId,
            'name' => trim((string) $payload['name']),
            'code' => strtoupper(trim((string) $payload['code'])),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        $this->assertBlockNameUnique($siteId, (string) $data['name']);

        try {
            $this->blockModel->insert($data, true);
        } catch (\Throwable $e) {
            throw new ConflictApiException('Blok kodu ayni site icinde benzersiz olmali');
        }

        $id = (int) $this->blockModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.block.create.success', ['entity_type' => 'block', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleBlock($id);
        $row = $this->blockModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Blok bulunamadi');
        }

        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['name', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = is_string($payload[$field]) ? trim((string) $payload[$field]) : $payload[$field];
            }
        }
        if (array_key_exists('code', $payload)) {
            $data['code'] = strtoupper(trim((string) $payload['code']));
        }
        if (array_key_exists('sort_order', $payload)) {
            $data['sort_order'] = (int) $payload['sort_order'];
        }
        if (array_key_exists('site_id', $payload)) {
            $data['site_id'] = (int) $payload['site_id'];
            $this->assertSiteExists($data['site_id']);
        }

        $nextSiteId = (int) ($data['site_id'] ?? $current['site_id']);
        $nextName = (string) ($data['name'] ?? $current['name']);
        $this->assertBlockNameUnique($nextSiteId, $nextName, $id);

        if ($data !== []) {
            try {
                $this->blockModel->update($id, $data);
            } catch (\Throwable $e) {
                throw new ConflictApiException('Blok kodu ayni site icinde benzersiz olmali');
            }
        }

        $updated = $this->show($id);
        $this->audit('site.block.update.success', ['entity_type' => 'block', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->blockModel->delete($id);
        $this->audit('site.block.delete.success', ['entity_type' => 'block', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertSiteExists(int $siteId): void
    {
        if (! is_array($this->siteModel->tenantFind($siteId))) {
            throw new NotFoundApiException('Ilgili site bulunamadi');
        }
    }

    private function assertAccessibleBlock(int $id): void
    {
        $row = Database::connect()->table('blocks')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Blok bulunamadi');
        }

        $request = service('request');
        $contextCompanyId = (int) ($request->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Blok bulunamadi');
        }
    }

    private function assertBlockNameUnique(int $siteId, string $name, ?int $exceptId = null): void
    {
        $builder = $this->blockModel->builder()
            ->select('id')
            ->where('site_id', $siteId)
            ->where('name', trim($name))
            ->where('deleted_at', null);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni site icinde blok adi benzersiz olmali');
        }
    }
}
