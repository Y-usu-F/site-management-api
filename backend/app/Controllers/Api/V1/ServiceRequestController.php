<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\ServiceRequestService;
use App\Validation\ServiceRequestValidation;
use Throwable;

class ServiceRequestController extends ApiController
{
    public function __construct(private readonly ServiceRequestService $service = new ServiceRequestService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Service request listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ServiceRequestValidation::createRules());
            return $this->ok('Service request olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Service request getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ServiceRequestValidation::updateRules());
            return $this->ok('Service request guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function assign($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], ServiceRequestValidation::assignRules());
            return $this->ok('Service request atandi', $this->service->assign((int) $id, (int) $payload['assigned_to_user_id']));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function resolve($id = null)
    {
        try {
            return $this->ok('Service request resolve edildi', $this->service->resolve((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function close($id = null)
    {
        try {
            return $this->ok('Service request kapatildi', $this->service->close((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Service request iptal edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
