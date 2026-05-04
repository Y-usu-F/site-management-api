<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\SiteModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

class SiteService extends BaseService
{
    public function __construct(private readonly SiteModel $siteModel = new SiteModel())
    {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'name', 'code', 'created_at'],
            'filterable' => ['status'],
            'default_sort' => 'id',
            'default_direction' => 'desc',
            'max_per_page' => 100,
        ]);

        $table = $this->siteModel->getTable();
        $builder = $this->siteModel->builder()
            ->select("{$table}.id, {$table}.public_id, {$table}.name, {$table}.code, {$table}.address, {$table}.status, {$table}.created_at, {$table}.updated_at")
            ->where("{$table}.deleted_at", null);
        if ($q['search'] !== '') {
            $builder->groupStart()
                ->like("{$table}.name", $q['search'])
                ->orLike("{$table}.code", $q['search'])
                ->groupEnd();
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }

        $total = (int) $builder->countAllResults(false);
        $rows = $builder->orderBy($q['sort'], $q['direction'])
            ->limit($q['per_page'], ($q['page'] - 1) * $q['per_page'])
            ->get()
            ->getResultArray();

        return ListQuery::envelope($q['page'], $q['per_page'], $total, $rows);
    }

    public function create(array $payload): array
    {
        $data = [
            'public_id' => $this->uuidV4(),
            'name' => trim((string) $payload['name']),
            'code' => strtoupper(trim((string) $payload['code'])),
            'address' => isset($payload['address']) ? trim((string) $payload['address']) : null,
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        try {
            $this->siteModel->insert($data, true);
        } catch (DatabaseException $e) {
            throw new ConflictApiException('Site kodu benzersiz olmali');
        }

        $id = (int) $this->siteModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.site.create.success', ['entity_type' => 'site', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleSite($id);
        $row = $this->siteModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Site bulunamadi');
        }

        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['name', 'address', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = is_string($payload[$field]) ? trim((string) $payload[$field]) : $payload[$field];
            }
        }
        if (array_key_exists('code', $payload)) {
            $data['code'] = strtoupper(trim((string) $payload['code']));
        }

        if ($data !== []) {
            try {
                $this->siteModel->update($id, $data);
            } catch (DatabaseException $e) {
                throw new ConflictApiException('Site kodu benzersiz olmali');
            }
        }

        $updated = $this->show($id);
        $this->audit('site.site.update.success', ['entity_type' => 'site', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->siteModel->delete($id);
        $this->audit('site.site.delete.success', ['entity_type' => 'site', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertAccessibleSite(int $id): void
    {
        $row = Database::connect()->table('sites')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Site bulunamadi');
        }

        $request = service('request');
        $contextCompanyId = (int) ($request->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Site bulunamadi');
        }
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}
