<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\OperationsSummaryService;
use Throwable;

class OperationsSummaryController extends ApiController
{
    public function __construct(
        private readonly OperationsSummaryService $service = new OperationsSummaryService()
    ) {
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Operations ozeti getirildi', $this->service->summary());
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}

