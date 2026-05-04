<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\MeterReadingPeriodService;
use App\Validation\MeterReadingPeriodValidation;
use Throwable;

class MeterReadingPeriodController extends ApiController
{
    public function __construct(private readonly MeterReadingPeriodService $service = new MeterReadingPeriodService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Meter reading period listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], MeterReadingPeriodValidation::createRules());
            return $this->ok('Meter reading period olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Meter reading period getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], MeterReadingPeriodValidation::updateRules());
            return $this->ok('Meter reading period guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function close($id = null)
    {
        try {
            return $this->ok('Meter reading period kapatildi', $this->service->close((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function lock($id = null)
    {
        try {
            return $this->ok('Meter reading period kilitlendi', $this->service->lock((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
