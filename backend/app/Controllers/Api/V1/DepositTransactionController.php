<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Finance\DepositTransactionService;
use Throwable;

class DepositTransactionController extends ApiController
{
    public function __construct(private readonly DepositTransactionService $service = new DepositTransactionService())
    {
    }

    public function index($depositId = null)
    {
        try {
            return $this->ok('Deposit transaction listesi getirildi', $this->service->listByDeposit((int) $depositId, $this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Deposit transaction getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
