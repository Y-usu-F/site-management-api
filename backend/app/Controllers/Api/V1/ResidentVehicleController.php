<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Resident\ResidentVehicleService;
use App\Validation\ResidentVehicleValidation;
use Throwable;

class ResidentVehicleController extends ApiController
{
    public function __construct(private readonly ResidentVehicleService $service = new ResidentVehicleService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Resident vehicle listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ResidentVehicleValidation::createRules());
            return $this->ok('Resident vehicle olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Resident vehicle getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ResidentVehicleValidation::updateRules());
            return $this->ok('Resident vehicle guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Resident vehicle silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
