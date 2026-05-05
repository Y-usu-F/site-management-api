<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Exceptions\ValidationApiException;
use App\Services\Common\ExcelExportService;
use App\Services\Common\ExcelImportService;
use App\Services\Site\SiteService;
use App\Validation\SiteValidation;
use Throwable;

class SiteController extends ApiController
{
    public function __construct(
        private readonly SiteService $siteService = new SiteService(),
        private readonly ExcelExportService $excelExportService = new ExcelExportService(),
        private readonly ExcelImportService $excelImportService = new ExcelImportService()
    ) {
    }

    public function index()
    {
        try {
            return $this->ok('Site listesi getirildi', $this->siteService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], SiteValidation::createRules());
            return $this->ok('Site olusturuldu', $this->siteService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Site getirildi', $this->siteService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], SiteValidation::updateRules());
            return $this->ok('Site guncellendi', $this->siteService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->siteService->delete((int) $id);
            return $this->ok('Site silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function bulkDelete()
    {
        try {
            $payload = $this->request->getJSON(true) ?? [];
            $ids = $this->extractBulkIds($payload);
            return $this->ok('Site toplu silindi', $this->siteService->bulkDelete($ids));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function export()
    {
        try {
            $rows = $this->siteService->exportRows($this->request->getGet());
            $binary = $this->excelExportService->buildXlsxBinary(['id', 'name', 'code', 'address', 'status'], $rows);
            $filename = 'sites_export_' . date('Ymd_His') . '.xlsx';

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($binary);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function import()
    {
        try {
            $file = $this->excelImportService->assertXlsxFile($this->request->getFile('file'));
            return $this->ok('Site excel ice aktarma tamamlandi', $this->siteService->importRows($this->excelImportService, $file));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function importTemplate()
    {
        try {
            $binary = $this->excelExportService->buildXlsxBinary(['name', 'code', 'address', 'status'], []);
            $filename = 'sites_import_template.xlsx';
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($binary);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return list<int>
     */
    private function extractBulkIds(array $payload): array
    {
        $ids = $payload['ids'] ?? null;
        if (! is_array($ids) || $ids === []) {
            throw new ValidationApiException('ids zorunludur', ['ids' => 'En az bir id gonderilmelidir.']);
        }

        $normalized = [];
        foreach ($ids as $id) {
            if (! is_numeric($id) || (int) $id <= 0) {
                throw new ValidationApiException('ids icindeki tum degerler pozitif integer olmali', ['ids' => 'Gecersiz id degeri var.']);
            }
            $normalized[] = (int) $id;
        }

        return array_values(array_unique($normalized));
    }
}
