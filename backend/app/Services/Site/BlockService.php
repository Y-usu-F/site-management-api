<?php

namespace App\Services\Site;

use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Exceptions\ValidationApiException;
use App\Libraries\ListQuery;
use App\Models\BlockModel;
use App\Models\SiteModel;
use App\Services\Common\ExcelImportService;
use App\Validation\BlockValidation;
use Config\Database;
use Config\Services;

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
        $companyId = $this->resolveSiteCompanyId($siteId);
        $data = [
            'company_id' => $companyId,
            'site_id' => $siteId,
            'name' => trim((string) $payload['name']),
            'code' => strtoupper(trim((string) $payload['code'])),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'active'),
        ];

        $this->assertBlockCodeUnique($companyId, $siteId, (string) $data['code']);

        try {
            $this->blockModel->insert($data, true);
        } catch (\Throwable $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new ConflictApiException('Blok kodu ayni site icinde benzersiz olmali');
            }
            throw $e;
        }

        $id = (int) $this->blockModel->getInsertID();
        $created = $this->show($id);
        $this->audit('site.block.create.success', ['entity_type' => 'block', 'entity_id' => $id, 'new_values' => $created]);
        return $created;
    }

    public function show(int $id): array
    {
        $this->assertAccessibleBlock($id);
        $row = Database::connect()->table('blocks')
            ->select('*')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray();
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
        $nextCompanyId = $this->resolveSiteCompanyId($nextSiteId);
        $nextCode = (string) ($data['code'] ?? $current['code']);
        $this->assertBlockCodeUnique($nextCompanyId, $nextSiteId, $nextCode, $id);

        if ($data !== []) {
            try {
                $this->blockModel->update($id, $data);
            } catch (\Throwable $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    throw new ConflictApiException('Blok kodu ayni site icinde benzersiz olmali');
                }
                throw $e;
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
            'sortable' => ['id', 'name', 'code', 'site_id', 'sort_order', 'created_at'],
            'filterable' => ['site_id', 'status'],
            'max_per_page' => 10000,
        ]);

        $builder = $this->blockModel->builder()
            ->select('id, site_id, name, code, sort_order, status')
            ->where('deleted_at', null);
        if ($q['search'] !== '') {
            $builder->groupStart()->like('name', $q['search'])->orLike('code', $q['search'])->groupEnd();
        }
        foreach ($q['filters'] as $field => $value) {
            $builder->where($field, $value);
        }
        return $builder->orderBy($q['sort'], $q['direction'])->get()->getResultArray();
    }

    /**
     * @return array{inserted_count:int,updated_count:int,skipped_count:int,error_rows:list<array<string,mixed>>}
     */
    public function importRows(ExcelImportService $excelImportService, \CodeIgniter\HTTP\Files\UploadedFile $file, ?int $siteIdContext = null): array
    {
        $parsed = $excelImportService->parseFirstSheet($file);
        if ($siteIdContext === null) {
            $excelImportService->assertRequiredHeaders(['site_id', 'name', 'code'], $parsed['headers']);
        } else {
            $excelImportService->assertRequiredHeaders(['name', 'code'], $parsed['headers']);
            $this->assertSiteExists($siteIdContext);
        }

        $validation = Services::validation();
        $insertedCount = 0;
        $skippedCount = 0;
        $errorRows = [];

        foreach ($parsed['rows'] as $index => $row) {
            $siteId = $siteIdContext ?? (int) ($row['site_id'] ?? 0);
            $payload = [
                'site_id' => $siteId,
                'name' => $row['name'] ?? '',
                'code' => $row['code'] ?? '',
                'sort_order' => ($row['sort_order'] ?? '') !== '' ? (int) $row['sort_order'] : 0,
                'status' => ($row['status'] ?? '') !== '' ? $row['status'] : 'active',
            ];

            $validation->reset();
            if (! $validation->setRules(BlockValidation::createRules())->run($payload)) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => $validation->getErrors()];
                continue;
            }

            try {
                $this->create($payload);
                $insertedCount++;
            } catch (ConflictApiException $e) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => ['code' => 'Ayni kod zaten mevcut.']];
            } catch (ValidationApiException|NotFoundApiException $e) {
                $skippedCount++;
                $errorRows[] = ['row' => $index + 2, 'errors' => ['site_id' => $e->getMessage()]];
            }
        }

        return [
            'inserted_count' => $insertedCount,
            'updated_count' => 0,
            'skipped_count' => $skippedCount,
            'error_rows' => $errorRows,
        ];
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

    private function resolveSiteCompanyId(int $siteId): int
    {
        $site = Database::connect()->table('sites')
            ->select('company_id')
            ->where('id', $siteId)
            ->where('deleted_at', null)
            ->get(1)
            ->getRowArray();

        if (! is_array($site) || ! isset($site['company_id'])) {
            throw new NotFoundApiException('Ilgili site bulunamadi');
        }

        return (int) $site['company_id'];
    }

    private function assertBlockCodeUnique(int $companyId, int $siteId, string $code, ?int $exceptId = null): void
    {
        $normalizedCode = strtoupper(trim($code));
        $builder = Database::connect()->table('blocks')
            ->select('id')
            ->where('company_id', $companyId)
            ->where('site_id', $siteId)
            ->where('code', $normalizedCode)
            ->where('deleted_at', null);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        if ($builder->get(1)->getRowArray() !== null) {
            throw new ConflictApiException('Blok kodu ayni site icinde benzersiz olmali');
        }
    }

    private function isUniqueConstraintViolation(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate entry')
            || str_contains($message, '1062')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'uq_blocks_company_site_code');
    }
}
