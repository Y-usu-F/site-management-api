<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Exceptions\ValidationApiException;
use App\Services\Common\ExcelExportService;
use App\Services\Common\ExcelImportService;
use App\Services\Site\FloorService;
use App\Validation\FloorValidation;
use Throwable;

class FloorController extends ApiController
{
    public function __construct(
        private readonly FloorService $floorService = new FloorService(),
        private readonly ExcelExportService $excelExportService = new ExcelExportService(),
        private readonly ExcelImportService $excelImportService = new ExcelImportService()
    ) {
    }

    public function index()
    {
        try {
            return $this->ok('Kat listesi getirildi', $this->floorService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], FloorValidation::createRules());
            return $this->ok('Kat olusturuldu', $this->floorService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Kat getirildi', $this->floorService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], FloorValidation::updateRules());
            return $this->ok('Kat guncellendi', $this->floorService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->floorService->delete((int) $id);
            return $this->ok('Kat silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function bulkDelete()
    {
        try {
            $ids = $this->extractBulkIds($this->request->getJSON(true) ?? []);
            return $this->ok('Kat toplu silindi', $this->floorService->bulkDelete($ids));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function export()
    {
        try {
            $rows = $this->floorService->exportRows($this->request->getGet());
            $binary = $this->excelExportService->buildXlsxBinary(['id', 'block_id', 'number', 'label', 'status'], $rows);
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="floors_export_' . date('Ymd_His') . '.xlsx"')
                ->setBody($binary);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function import()
    {
        try {
            $file = $this->excelImportService->assertXlsxFile($this->request->getFile('file'));
            $blockId = $this->request->getGet('block_id');
            $blockIdContext = is_numeric($blockId) && (int) $blockId > 0 ? (int) $blockId : null;
            return $this->ok('Kat excel ice aktarma tamamlandi', $this->floorService->importRows($this->excelImportService, $file, $blockIdContext));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function importTemplate()
    {
        try {
            $binary = $this->excelExportService->buildXlsxBinary(['site_id', 'block_id', 'number', 'label', 'sort_order', 'status'], []);
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="floors_import_template.xlsx"')
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
