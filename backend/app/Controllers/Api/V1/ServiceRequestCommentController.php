<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\ServiceRequestCommentService;
use App\Validation\ServiceRequestCommentValidation;
use Throwable;

class ServiceRequestCommentController extends ApiController
{
    public function __construct(private readonly ServiceRequestCommentService $service = new ServiceRequestCommentService())
    {
    }

    public function index($id = null)
    {
        try {
            return $this->ok('Service request comment listesi getirildi', $this->service->listByRequest((int) $id, $this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ServiceRequestCommentValidation::createRules());
            return $this->ok('Service request comment olusturuldu', $this->service->create((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
