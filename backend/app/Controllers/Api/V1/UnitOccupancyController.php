<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Resident\UnitOccupancyService;
use App\Validation\UnitOccupancyValidation;
use Throwable;

class UnitOccupancyController extends ApiController
{
    public function __construct(private readonly UnitOccupancyService $service = new UnitOccupancyService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Unit occupancy listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], UnitOccupancyValidation::createRules());
            return $this->ok('Unit occupancy olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Unit occupancy getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], UnitOccupancyValidation::updateRules());
            return $this->ok('Unit occupancy guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Unit occupancy silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
