<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Site\SiteService;
use App\Validation\SiteValidation;
use Throwable;

class SiteController extends ApiController
{
    public function __construct(private readonly SiteService $siteService = new SiteService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Site listesi getirildi', $this->siteService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], SiteValidation::createRules());
            return $this->ok('Site olusturuldu', $this->siteService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Site getirildi', $this->siteService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], SiteValidation::updateRules());
            return $this->ok('Site guncellendi', $this->siteService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->siteService->delete((int) $id);
            return $this->ok('Site silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
