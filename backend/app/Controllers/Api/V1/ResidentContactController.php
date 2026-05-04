<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Resident\ResidentContactService;
use App\Validation\ResidentContactValidation;
use Throwable;

class ResidentContactController extends ApiController
{
    public function __construct(private readonly ResidentContactService $service = new ResidentContactService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Resident contact listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ResidentContactValidation::createRules());
            return $this->ok('Resident contact olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ResidentContactValidation::updateRules());
            return $this->ok('Resident contact guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Resident contact silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
