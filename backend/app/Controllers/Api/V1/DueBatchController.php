<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\DueBatchService;
use App\Validation\DueBatchValidation;
use Throwable;

class DueBatchController extends ApiController
{
    public function __construct(private readonly DueBatchService $service = new DueBatchService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Due batch listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Due batch getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DueBatchValidation::createRules());
            return $this->ok('Due batch olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
