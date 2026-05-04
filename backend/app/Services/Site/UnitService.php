<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\FloorModel;
use App\Models\UnitModel;
use Config\Database;

class UnitService extends BaseService
{
    public function __construct(
        private readonly UnitModel $unitModel = new UnitModel(),
        private readonly FloorModel $floorModel = new FloorModel()
    ) {
        parent::__construct();
    }

    public function list(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'site_id', 'block_id', 'floor_id', 'unit_no', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'floor_id', 'status', 'type'],
        ]);

        $builder = $this->unitModel->builder()->select('*');
        if ($q['search'] !== '') {
            $builder->groupStart()
                ->like('unit_no', $q['search'])
                ->orLike('occupant_name', $q['search'])
                ->groupEnd();
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
        $floor = $this->assertFloorExists((int) $payload['floor_id']);
        $siteId = (int) $payload['site_id'];
        $blockId = (int) $payload['block_id'];
        if ((int) $floor['site_id'] !== $siteId || (int) $floor['block_id'] !== $blockId) {
            throw new ConflictApiException('Bagimsiz bolumun floor/block/site iliskisi tutarsiz');
        }

        $data = [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => (int) $payload['floor_id'],
            'unit_no' => trim((string) $payload['unit_no']),
            'type' => isset($payload['type']) ? trim((string) $payload['type']) : null,
            'gross_area' => $payload['gross_area'] ?? null,
            'net_area' => $payload['net_area'] ?? null,
            'occupant_name' => isset($payload['occupant_name']) ? trim((string) $payload['occupant_name']) : null,
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        try {
            $this->unitModel->insert($data, true);
        } catch (\Throwable $e) {
            throw new ConflictApiException('Ayni katta bagimsiz bolum numarasi benzersiz olmali');
        }

        $id = (int) $this->unitModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.unit.create.success', ['entity_type' => 'unit', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleUnit($id);
        $row = $this->unitModel->tenantFind($id);
        if (! is_array($row)) {
            throw new NotFoundApiException('Bagimsiz bolum bulunamadi');
        }
        return $row;
    }

    public function update(int $id, array $payload): array
    {
        $current = $this->show($id);
        $data = [];
        foreach (['unit_no', 'type', 'occupant_name', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = trim((string) $payload[$field]);
            }
        }
        foreach (['site_id', 'block_id', 'floor_id'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = (int) $payload[$field];
            }
        }
        foreach (['gross_area', 'net_area'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }

        $nextFloorId = (int) ($data['floor_id'] ?? $current['floor_id']);
        $nextSiteId = (int) ($data['site_id'] ?? $current['site_id']);
        $nextBlockId = (int) ($data['block_id'] ?? $current['block_id']);
        $floor = $this->assertFloorExists($nextFloorId);
        if ((int) $floor['site_id'] !== $nextSiteId || (int) $floor['block_id'] !== $nextBlockId) {
            throw new ConflictApiException('Bagimsiz bolumun floor/block/site iliskisi tutarsiz');
        }

        if ($data !== []) {
            try {
                $this->unitModel->update($id, $data);
            } catch (\Throwable $e) {
                throw new ConflictApiException('Ayni katta bagimsiz bolum numarasi benzersiz olmali');
            }
        }

        $updated = $this->show($id);
        $this->audit('site.unit.update.success', ['entity_type' => 'unit', 'entity_id' => $id, 'old_values' => $current, 'new_values' => $updated]);
        return $updated;
    }

    public function delete(int $id): void
    {
        $current = $this->show($id);
        $this->unitModel->delete($id);
        $this->audit('site.unit.delete.success', ['entity_type' => 'unit', 'entity_id' => $id, 'old_values' => $current]);
    }

    private function assertFloorExists(int $floorId): array
    {
        $floor = $this->floorModel->tenantFind($floorId);
        if (! is_array($floor)) {
            throw new NotFoundApiException('Ilgili kat bulunamadi');
        }
        return $floor;
    }

    private function assertAccessibleUnit(int $id): void
    {
        $row = Database::connect()->table('units')->where('id', $id)->get()->getRowArray();
        if (! is_array($row)) {
            throw new NotFoundApiException('Bagimsiz bolum bulunamadi');
        }

        $request = service('request');
        $contextCompanyId = (int) ($request->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $row['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        if (($row['deleted_at'] ?? null) !== null) {
            throw new NotFoundApiException('Bagimsiz bolum bulunamadi');
        }
    }
}
