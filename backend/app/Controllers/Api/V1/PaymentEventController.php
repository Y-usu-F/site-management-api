<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\PaymentEventService;
use Throwable;

class PaymentEventController extends ApiController
{
    public function __construct(private readonly PaymentEventService $service = new PaymentEventService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Payment event listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Payment event getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
