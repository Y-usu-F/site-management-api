<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Resident\ResidentProfileService;
use App\Validation\ResidentProfileValidation;
use Throwable;

class ResidentProfileController extends ApiController
{
    public function __construct(private readonly ResidentProfileService $service = new ResidentProfileService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Resident listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ResidentProfileValidation::createRules());
            return $this->ok('Resident olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Resident getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ResidentProfileValidation::updateRules());
            return $this->ok('Resident guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Resident silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
