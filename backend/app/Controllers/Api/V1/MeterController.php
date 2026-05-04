<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\MeterService;
use App\Validation\MeterValidation;
use Throwable;

class MeterController extends ApiController
{
    public function __construct(private readonly MeterService $service = new MeterService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Meter listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], MeterValidation::createRules());
            return $this->ok('Meter olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Meter getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], MeterValidation::updateRules());
            return $this->ok('Meter guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Meter silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
