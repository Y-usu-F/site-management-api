<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Communication\NotificationRecipientService;
use Throwable;

class NotificationRecipientController extends ApiController
{
    public function __construct(
        private readonly NotificationRecipientService $service = new NotificationRecipientService()
    ) {
    }

    public function index()
    {
        try {
            return $this->ok('Notification recipient listesi getirildi', $this->service->list($this->request->getGet()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function unreadCount()
    {
        try {
            return $this->ok('Notification unread count getirildi', $this->service->unreadCount());
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function markAllRead()
    {
        try {
            return $this->ok('Notification recipient toplu mark-read tamamlandi', $this->service->markAllRead());
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Notification recipient getirildi', $this->service->show((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function markRead($id = null)
    {
        try {
            return $this->ok('Notification recipient mark-read tamamlandi', $this->service->markRead((int) $id));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
