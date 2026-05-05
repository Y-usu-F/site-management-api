<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Exceptions\ValidationApiException;
use App\Services\Common\ExcelExportService;
use App\Services\Common\ExcelImportService;
use App\Services\Site\BlockService;
use App\Validation\BlockValidation;
use Throwable;

class BlockController extends ApiController
{
    public function __construct(
        private readonly BlockService $blockService = new BlockService(),
        private readonly ExcelExportService $excelExportService = new ExcelExportService(),
        private readonly ExcelImportService $excelImportService = new ExcelImportService()
    ) {
    }

    public function index()
    {
        try {
            return $this->ok('Blok listesi getirildi', $this->blockService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], BlockValidation::createRules());
            return $this->ok('Blok olusturuldu', $this->blockService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Blok getirildi', $this->blockService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], BlockValidation::updateRules());
            return $this->ok('Blok guncellendi', $this->blockService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->blockService->delete((int) $id);
            return $this->ok('Blok silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function bulkDelete()
    {
        try {
            $ids = $this->extractBulkIds($this->request->getJSON(true) ?? []);
            return $this->ok('Blok toplu silindi', $this->blockService->bulkDelete($ids));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function export()
    {
        try {
            $rows = $this->blockService->exportRows($this->request->getGet());
            $binary = $this->excelExportService->buildXlsxBinary(['id', 'site_id', 'name', 'code', 'sort_order', 'status'], $rows);
            $filename = 'blocks_export_' . date('Ymd_His') . '.xlsx';
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
            $siteId = $this->request->getGet('site_id');
            $siteIdContext = is_numeric($siteId) && (int) $siteId > 0 ? (int) $siteId : null;
            return $this->ok('Blok excel ice aktarma tamamlandi', $this->blockService->importRows($this->excelImportService, $file, $siteIdContext));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function importTemplate()
    {
        try {
            $binary = $this->excelExportService->buildXlsxBinary(['site_id', 'name', 'code', 'sort_order', 'status'], []);
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="blocks_import_template.xlsx"')
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
