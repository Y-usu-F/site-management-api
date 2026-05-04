<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\PaymentService;
use App\Validation\PaymentValidation;
use Throwable;

class PaymentController extends ApiController
{
    public function __construct(private readonly PaymentService $service = new PaymentService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Payment listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Payment getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function createManual()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], PaymentValidation::manualCreateRules());
            return $this->ok('Manual payment olusturuldu', $this->service->createManual($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Payment iptal edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
