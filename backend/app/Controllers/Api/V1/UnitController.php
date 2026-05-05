<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Exceptions\ValidationApiException;
use App\Services\Common\ExcelExportService;
use App\Services\Common\ExcelImportService;
use App\Services\Site\UnitService;
use App\Validation\UnitValidation;
use Throwable;

class UnitController extends ApiController
{
    public function __construct(
        private readonly UnitService $unitService = new UnitService(),
        private readonly ExcelExportService $excelExportService = new ExcelExportService(),
        private readonly ExcelImportService $excelImportService = new ExcelImportService()
    ) {
    }

    public function index()
    {
        try {
            return $this->ok('Bagimsiz bolum listesi getirildi', $this->unitService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], UnitValidation::createRules());
            return $this->ok('Bagimsiz bolum olusturuldu', $this->unitService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Bagimsiz bolum getirildi', $this->unitService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], UnitValidation::updateRules());
            return $this->ok('Bagimsiz bolum guncellendi', $this->unitService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->unitService->delete((int) $id);
            return $this->ok('Bagimsiz bolum silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function bulkDelete()
    {
        try {
            $ids = $this->extractBulkIds($this->request->getJSON(true) ?? []);
            return $this->ok('Bagimsiz bolum toplu silindi', $this->unitService->bulkDelete($ids));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function export()
    {
        try {
            $rows = $this->unitService->exportRows($this->request->getGet());
            $binary = $this->excelExportService->buildXlsxBinary(['id', 'site_id', 'block_id', 'floor_id', 'unit_no', 'type', 'gross_area', 'net_area', 'land_share', 'status'], $rows);
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="units_export_' . date('Ymd_His') . '.xlsx"')
                ->setBody($binary);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function import()
    {
        try {
            $file = $this->excelImportService->assertXlsxFile($this->request->getFile('file'));
            $floorId = $this->request->getGet('floor_id');
            $floorIdContext = is_numeric($floorId) && (int) $floorId > 0 ? (int) $floorId : null;
            return $this->ok('Bagimsiz bolum excel ice aktarma tamamlandi', $this->unitService->importRows($this->excelImportService, $file, $floorIdContext));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function importTemplate()
    {
        try {
            $binary = $this->excelExportService->buildXlsxBinary(['site_id', 'block_id', 'floor_id', 'unit_no', 'type', 'gross_area', 'net_area', 'land_share', 'status'], []);
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="units_import_template.xlsx"')
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
