<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\DepositService;
use App\Validation\DepositValidation;
use Throwable;

class DepositController extends ApiController
{
    public function __construct(private readonly DepositService $service = new DepositService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Deposit listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::createRules());
            return $this->ok('Deposit olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Deposit getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::updateRules());
            return $this->ok('Deposit guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function receive($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::receiveRules());
            return $this->ok('Deposit receive islemi tamamlandi', $this->service->receive((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function refund($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::refundRules());
            return $this->ok('Deposit refund islemi tamamlandi', $this->service->refund((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function deduct($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::deductRules());
            return $this->ok('Deposit deduction islemi tamamlandi', $this->service->deduct((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function applyToDebt($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::applyToDebtRules());
            return $this->ok('Deposit borca mahsup islemi tamamlandi', $this->service->applyToDebt((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], DepositValidation::cancelRules());
            return $this->ok('Deposit iptal edildi', $this->service->cancel((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
