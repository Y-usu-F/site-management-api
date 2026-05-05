<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Exceptions\ValidationApiException;
use App\Libraries\ListQuery;
use App\Models\FloorModel;
use App\Models\UnitModel;
use App\Services\Common\ExcelImportService;
use App\Validation\UnitValidation;
use Config\Database;
use Config\Services;

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

        $companyId = (int) $floor['company_id'];
        $unitNo = trim((string) $payload['unit_no']);
        $data = [
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => (int) $payload['floor_id'],
            'unit_no' => $unitNo,
            'type' => isset($payload['type']) ? trim((string) $payload['type']) : null,
            'gross_area' => $payload['gross_area'] ?? null,
            'net_area' => $payload['net_area'] ?? null,
            'land_share' => $payload['land_share'] ?? null,
            'occupant_name' => isset($payload['occupant_name']) ? trim((string) $payload['occupant_name']) : null,
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        $this->assertUnitNoUnique($companyId, (int) $floor['id'], $unitNo);

        try {
            $this->unitModel->insert($data, true);
        } catch (\Throwable $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new ConflictApiException('Ayni katta bagimsiz bolum numarasi benzersiz olmali');
            }
            throw $e;
        }

        $id = (int) $this->unitModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.unit.create.success', ['entity_type' => 'unit', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleUnit($id);
        $row = Database::connect()->table('units')
            ->select('*')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray();
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
        foreach (['gross_area', 'net_area', 'land_share'] as $field) {
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
        $nextUnitNo = (string) ($data['unit_no'] ?? $current['unit_no']);
        $this->assertUnitNoUnique((int) $floor['company_id'], $nextFloorId, $nextUnitNo, $id);

        if ($data !== []) {
            try {
                $this->unitModel->update($id, $data);
            } catch (\Throwable $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    throw new ConflictApiException('Ayni katta bagimsiz bolum numarasi benzersiz olmali');
                }
                throw $e;
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

    /**
     * @param list<int> $ids
     * @return array{deleted_count:int,skipped_count:int,errors:list<array<string,mixed>>}
     */
    public function bulkDelete(array $ids): array
    {
        if ($ids === []) {
            throw new ValidationApiException('Silinecek kayit secilmedi', ['ids' => 'ids zorunludur.']);
        }

        $db = Database::connect();
        $db->transException(true)->transStart();
        foreach ($ids as $id) {
            $this->delete((int) $id);
        }
        $db->transComplete();

        return ['deleted_count' => count($ids), 'skipped_count' => 0, 'errors' => []];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function exportRows(array $query): array
    {
        $q = ListQuery::normalize($query, [
            'sortable' => ['id', 'site_id', 'block_id', 'floor_id', 'unit_no', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'floor_id', 'status', 'type'],
            'max_per_page' => 10000,
        ]);

        $builder = $this->unitModel->builder()
            ->select('id, site_id, block_id, floor_id, unit_no, type, gross_area, net_area, land_share, status')
            ->where('deleted_at', null);
        if ($q['search'] !== '') {
            $builder->groupStart()->like('unit_no', $q['search'])->orLike('occupant_name', $q['search'])->groupEnd();
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        return $builder->orderBy($q['sort'], $q['direction'])->get()->getResultArray();
    }

    /**
     * @return array{inserted_count:int,updated_count:int,skipped_count:int,error_rows:list<array<string,mixed>>}
     */
    public function importRows(ExcelImportService $excelImportService, \CodeIgniter\HTTP\Files\UploadedFile $file, ?int $floorIdContext = null): array
    {
        $parsed = $excelImportService->parseFirstSheet($file);
        if ($floorIdContext === null) {
            $excelImportService->assertRequiredHeaders(['site_id', 'block_id', 'floor_id', 'unit_no'], $parsed['headers']);
        } else {
            $excelImportService->assertRequiredHeaders(['unit_no'], $parsed['headers']);
            $this->assertFloorExists($floorIdContext);
        }

        $validation = Services::validation();
        $insertedCount = 0;
        $skippedCount = 0;
        $errorRows = [];

        foreach ($parsed['rows'] as $index => $row) {
            $contextFloor = $floorIdContext !== null ? $this->assertFloorExists($floorIdContext) : null;
            $floorId = $floorIdContext ?? (int) ($row['floor_id'] ?? 0);
            $siteId = $contextFloor !== null ? (int) $contextFloor['site_id'] : (int) ($row['site_id'] ?? 0);
            $blockId = $contextFloor !== null ? (int) $contextFloor['block_id'] : (int) ($row['block_id'] ?? 0);

            $payload = [
                'site_id' => $siteId,
                'block_id' => $blockId,
                'floor_id' => $floorId,
                'unit_no' => $row['unit_no'] ?? '',
                'type' => $row['type'] ?? null,
                'gross_area' => ($row['gross_area'] ?? '') !== '' ? $row['gross_area'] : null,
                'net_area' => ($row['net_area'] ?? '') !== '' ? $row['net_area'] : null,
                'land_share' => ($row['land_share'] ?? '') !== '' ? $row['land_share'] : null,
                'status' => ($row['status'] ?? '') !== '' ? $row['status'] : 'active',
            ];

            $validation->reset();
            if (! $validation->setRules(UnitValidation::createRules())->run($payload)) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => $validation->getErrors()];
                continue;
            }

            try {
                $this->create($payload);
                $insertedCount++;
            } catch (ConflictApiException $e) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => ['unit_no' => 'Ayni katta unit_no zaten mevcut.']];
            } catch (ValidationApiException|NotFoundApiException $e) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => ['floor_id' => $e->getMessage()]];
            }
        }

        return [
            'inserted_count' => $insertedCount,
            'updated_count' => 0,
            'skipped_count' => $skippedCount,
            'error_rows' => $errorRows,
        ];
    }

    private function assertFloorExists(int $floorId): array
    {
        $floor = Database::connect()->table('floors')
            ->select('id, company_id, site_id, block_id, deleted_at')
            ->where('id', $floorId)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray();
        if (! is_array($floor)) {
            throw new NotFoundApiException('Ilgili kat bulunamadi');
        }

        $request = service('request');
        $contextCompanyId = (int) ($request->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $floor['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        return $floor;
    }

    private function assertUnitNoUnique(int $companyId, int $floorId, string $unitNo, ?int $exceptId = null): void
    {
        $builder = Database::connect()->table('units')
            ->select('id')
            ->where('company_id', $companyId)
            ->where('floor_id', $floorId)
            ->where('unit_no', trim($unitNo))
            ->where('deleted_at', null);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni katta bagimsiz bolum numarasi benzersiz olmali');
        }
    }

    private function isUniqueConstraintViolation(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate entry')
            || str_contains($message, '1062')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'uq_units_company_floor_unit_no');
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
