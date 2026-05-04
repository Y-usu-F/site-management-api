<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Site\UnitService;
use App\Validation\UnitValidation;
use Throwable;

class UnitController extends ApiController
{
    public function __construct(private readonly UnitService $unitService = new UnitService())
    {
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
}
