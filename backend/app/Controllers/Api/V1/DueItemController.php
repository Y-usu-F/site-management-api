<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\DueItemService;
use App\Validation\DueItemValidation;
use Throwable;

class DueItemController extends ApiController
{
    public function __construct(private readonly DueItemService $service = new DueItemService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Due item listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Due item getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DueItemValidation::updateRules());
            return $this->ok('Due item guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Due item iptal edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
