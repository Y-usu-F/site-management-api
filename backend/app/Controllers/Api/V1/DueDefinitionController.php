<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\DueDefinitionService;
use App\Validation\DueDefinitionValidation;
use Throwable;

class DueDefinitionController extends ApiController
{
    public function __construct(private readonly DueDefinitionService $service = new DueDefinitionService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Due definition listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DueDefinitionValidation::createRules());
            return $this->ok('Due definition olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Due definition getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DueDefinitionValidation::updateRules());
            return $this->ok('Due definition guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Due definition silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
