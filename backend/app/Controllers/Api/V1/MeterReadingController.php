<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\ConsumptionReportService;
use App\Services\Operation\MeterReadingService;
use App\Validation\MeterReadingValidation;
use Throwable;

class MeterReadingController extends ApiController
{
    public function __construct(
        private readonly MeterReadingService $service = new MeterReadingService(),
        private readonly ConsumptionReportService $reportService = new ConsumptionReportService()
    ) {
    }

    public function index()
    {
        try {
            return $this->ok('Meter reading listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], MeterReadingValidation::createRules());
            return $this->ok('Meter reading olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Meter reading getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], MeterReadingValidation::updateRules());
            return $this->ok('Meter reading guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function approve($id = null)
    {
        try {
            return $this->ok('Meter reading approve edildi', $this->service->approve((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function reject($id = null)
    {
        try {
            $reason = ($this->request->getJSON(true) ?? [])['rejected_reason'] ?? null;
            return $this->ok('Meter reading reject edildi', $this->service->reject((int) $id, $reason !== null ? (string) $reason : null));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Meter reading cancel edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function generateConsumptionReport($id = null)
    {
        try {
            return $this->ok('Consumption report olusturuldu', $this->reportService->generateFromReading((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
