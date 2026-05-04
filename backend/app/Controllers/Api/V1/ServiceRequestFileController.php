<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\ServiceRequestFileService;
use App\Validation\ServiceRequestFileValidation;
use Throwable;

class ServiceRequestFileController extends ApiController
{
    public function __construct(private readonly ServiceRequestFileService $service = new ServiceRequestFileService())
    {
    }

    public function index($id = null)
    {
        try {
            return $this->ok('Service request file listesi getirildi', $this->service->listByRequest((int) $id, $this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ServiceRequestFileValidation::createRules());
            return $this->ok('Service request file metadata olusturuldu', $this->service->create((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($fileId = null)
    {
        try {
            $this->service->delete((int) $fileId);
            return $this->ok('Service request file silindi');
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
