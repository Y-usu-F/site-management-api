<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Communication\AnnouncementService;
use App\Validation\AnnouncementValidation;
use Throwable;

class AnnouncementController extends ApiController
{
    public function __construct(private readonly AnnouncementService $service = new AnnouncementService())
    {
    }

    public function index()
    {
        try {
            return $this->ok('Announcement listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function create()
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], AnnouncementValidation::createRules());
            return $this->ok('Announcement olusturuldu', $this->service->create($payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Announcement getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail($this->request->getJSON(true) ?? [], AnnouncementValidation::updateRules());
            return $this->ok('Announcement guncellendi', $this->service->update((int) $id, $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $this->service->delete((int) $id);
            return $this->ok('Announcement silindi');
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function publish($id = null)
    {
        try {
            return $this->ok('Announcement publish edildi', $this->service->publish((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function archive($id = null)
    {
        try {
            return $this->ok('Announcement archive edildi', $this->service->archive((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function cancel($id = null)
    {
        try {
            return $this->ok('Announcement cancel edildi', $this->service->cancel((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function markRead($id = null)
    {
        try {
            return $this->ok('Announcement mark-read basarili', $this->service->markRead((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function reads($id = null)
    {
        try {
            return $this->ok('Announcement read listesi getirildi', $this->service->listReads((int) $id, $this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function targets($id = null)
    {
        try {
            return $this->ok('Announcement target listesi getirildi', $this->service->listTargets((int) $id, $this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
