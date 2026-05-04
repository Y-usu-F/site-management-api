<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\RequestCategoryService;
use App\Validation\RequestCategoryValidation;
use Throwable;

class RequestCategoryController extends ApiController
{
    public function __construct(private readonly RequestCategoryService $service = new RequestCategoryService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Request category listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], RequestCategoryValidation::createRules());
            return $this->ok('Request category olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Request category getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], RequestCategoryValidation::updateRules());
            return $this->ok('Request category guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Request category silindi');
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
