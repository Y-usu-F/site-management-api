<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Site\BlockService;
use App\Validation\BlockValidation;
use Throwable;

class BlockController extends ApiController
{
    public function __construct(private readonly BlockService $blockService = new BlockService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Blok listesi getirildi', $this->blockService->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], BlockValidation::createRules());
            return $this->ok('Blok olusturuldu', $this->blockService->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Blok getirildi', $this->blockService->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], BlockValidation::updateRules());
            return $this->ok('Blok guncellendi', $this->blockService->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->blockService->delete((int) $id);
            return $this->ok('Blok silindi', ['id' => (int) $id]);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
