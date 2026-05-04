<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\ConsumptionReportService;
use Throwable;

class ConsumptionReportController extends ApiController
{
    public function __construct(private readonly ConsumptionReportService $service = new ConsumptionReportService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Consumption report listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Consumption report getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Consumption report iptal edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
