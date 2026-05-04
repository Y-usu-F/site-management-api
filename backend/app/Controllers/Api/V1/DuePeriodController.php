<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\DuePeriodService;
use App\Validation\DuePeriodValidation;
use Throwable;

class DuePeriodController extends ApiController
{
    public function __construct(private readonly DuePeriodService $service = new DuePeriodService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Due period listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DuePeriodValidation::createRules());
            return $this->ok('Due period olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Due period getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DuePeriodValidation::updateRules());
            return $this->ok('Due period guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Due period silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function close($id = null)
    {
        try {
            return $this->ok('Due period kapatildi', $this->service->close((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function lock($id = null)
    {
        try {
            return $this->ok('Due period kilitlendi', $this->service->lock((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
