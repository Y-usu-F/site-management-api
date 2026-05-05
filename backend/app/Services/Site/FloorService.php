<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Exceptions\ValidationApiException;
use App\Libraries\ListQuery;
use App\Models\BlockModel;
use App\Models\FloorModel;
use App\Services\Common\ExcelImportService;
use App\Validation\FloorValidation;
use Config\Database;
use Config\Services;

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
        $companyId = (int) $block['company_id'];
        $floorNumber = (int) $payload['number'];

        $data = [
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => (int) $payload['block_id'],
            'number' => $floorNumber,
            'label' => isset($payload['label']) ? trim((string) $payload['label']) : null,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        $this->assertFloorNumberUnique($companyId, (int) $block['id'], $floorNumber);

        try {
            $this->floorModel->insert($data, true);
        } catch (\Throwable $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new ConflictApiException('Ayni blokta kat numarasi benzersiz olmali');
            }
            throw $e;
        }

        $id = (int) $this->floorModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.floor.create.success', ['entity_type' => 'floor', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleFloor($id);
        $row = Database::connect()->table('floors')
            ->select('*')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray();
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
        $nextNumber = (int) ($data['number'] ?? $current['number']);
        $this->assertFloorNumberUnique((int) $block['company_id'], $nextBlockId, $nextNumber, $id);

        if ($data !== []) {
            try {
                $this->floorModel->update($id, $data);
            } catch (\Throwable $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    throw new ConflictApiException('Ayni blokta kat numarasi benzersiz olmali');
                }
                throw $e;
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
            'sortable' => ['id', 'site_id', 'block_id', 'number', 'sort_order', 'created_at'],
            'filterable' => ['site_id', 'block_id', 'status'],
            'max_per_page' => 10000,
        ]);

        $builder = $this->floorModel->builder()
            ->select('id, block_id, number, label, status')
            ->where('deleted_at', null);
        if ($q['search'] !== '') {
            $builder->groupStart()->like('label', $q['search'])->orLike('number', $q['search'])->groupEnd();
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        return $builder->orderBy($q['sort'], $q['direction'])->get()->getResultArray();
    }

    /**
     * @return array{inserted_count:int,updated_count:int,skipped_count:int,error_rows:list<array<string,mixed>>}
     */
    public function importRows(ExcelImportService $excelImportService, \CodeIgniter\HTTP\Files\UploadedFile $file, ?int $blockIdContext = null): array
    {
        $parsed = $excelImportService->parseFirstSheet($file);
        if ($blockIdContext === null) {
            $excelImportService->assertRequiredHeaders(['site_id', 'block_id', 'number'], $parsed['headers']);
        } else {
            $excelImportService->assertRequiredHeaders(['number'], $parsed['headers']);
            $this->assertBlockExists($blockIdContext);
        }

        $validation = Services::validation();
        $insertedCount = 0;
        $skippedCount = 0;
        $errorRows = [];

        foreach ($parsed['rows'] as $index => $row) {
            $contextBlock = $blockIdContext !== null ? $this->assertBlockExists($blockIdContext) : null;
            $blockId = $blockIdContext ?? (int) ($row['block_id'] ?? 0);
            $siteId = $contextBlock !== null ? (int) $contextBlock['site_id'] : (int) ($row['site_id'] ?? 0);

            $payload = [
                'site_id' => $siteId,
                'block_id' => $blockId,
                'number' => (int) ($row['number'] ?? 0),
                'label' => $row['label'] ?? null,
                'sort_order' => ($row['sort_order'] ?? '') !== '' ? (int) $row['sort_order'] : 0,
                'status' => ($row['status'] ?? '') !== '' ? $row['status'] : 'active',
            ];

            $validation->reset();
            if (! $validation->setRules(FloorValidation::createRules())->run($payload)) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => $validation->getErrors()];
                continue;
            }

            try {
                $this->create($payload);
                $insertedCount++;
            } catch (ConflictApiException $e) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => ['number' => 'Ayni blokta kat numarasi zaten mevcut.']];
            } catch (ValidationApiException|NotFoundApiException $e) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => ['block_id' => $e->getMessage()]];
            }
        }

        return [
            'inserted_count' => $insertedCount,
            'updated_count' => 0,
            'skipped_count' => $skippedCount,
            'error_rows' => $errorRows,
        ];
    }

    private function assertBlockExists(int $blockId): array
    {
        $block = Database::connect()->table('blocks')
            ->select('id, company_id, site_id, deleted_at')
            ->where('id', $blockId)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray();
        if (! is_array($block)) {
            throw new NotFoundApiException('Ilgili blok bulunamadi');
        }

        $request = service('request');
        $contextCompanyId = (int) ($request->company_id ?? 0);
        if ($contextCompanyId > 0 && (int) $block['company_id'] !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        return $block;
    }

    private function assertFloorNumberUnique(int $companyId, int $blockId, int $number, ?int $exceptId = null): void
    {
        $builder = Database::connect()->table('floors')
            ->select('id')
            ->where('company_id', $companyId)
            ->where('block_id', $blockId)
            ->where('number', $number)
            ->where('deleted_at', null);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Ayni blokta kat numarasi benzersiz olmali');
        }
    }

    private function isUniqueConstraintViolation(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate entry')
            || str_contains($message, '1062')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'uq_floors_company_block_number');
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
