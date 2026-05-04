<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\WorkOrderService;
use App\Validation\WorkOrderValidation;
use Throwable;

class WorkOrderController extends ApiController
{
    public function __construct(private readonly WorkOrderService $service = new WorkOrderService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Work order listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], WorkOrderValidation::createRules());
            return $this->ok('Work order olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Work order getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], WorkOrderValidation::updateRules());
            return $this->ok('Work order guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function start($id = null)
    {
        try {
            return $this->ok('Work order baslatildi', $this->service->start((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function complete($id = null)
    {
        try {
            return $this->ok('Work order tamamlandi', $this->service->complete((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Work order iptal edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
