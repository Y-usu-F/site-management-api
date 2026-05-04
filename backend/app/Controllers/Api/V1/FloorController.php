<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Site\FloorService;
use App\Validation\FloorValidation;
use Throwable;

class FloorController extends ApiController
{
    public function __construct(private readonly FloorService $floorService = new FloorService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Kat listesi getirildi', $this->floorService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], FloorValidation::createRules());
            return $this->ok('Kat olusturuldu', $this->floorService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Kat getirildi', $this->floorService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], FloorValidation::updateRules());
            return $this->ok('Kat guncellendi', $this->floorService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->floorService->delete((int) $id);
            return $this->ok('Kat silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
