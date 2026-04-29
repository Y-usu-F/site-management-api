<?php

namespace App\Controllers\Api\Auth;

use App\Core\ApiController;
use App\Services\Auth\SessionService;
use Throwable;

class AuthSessionController extends ApiController
{
    public function __construct(
        private readonly SessionService $sessionService = new SessionService()
    ) {
    }

    public function index()
    {
        try {
            $userId = (int) ($this->request->user->id ?? 0);
            $currentSessionId = $this->resolveCurrentSessionId();

            return $this->ok('Oturumlar listelendi', $this->sessionService->listSessions($userId, $currentSessionId));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $userId = (int) ($this->request->user->id ?? 0);
            $sessionId = (int) $id;

            return $this->ok('Oturum sonlandirildi', $this->sessionService->revokeSession($userId, $sessionId));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function revokeAll()
    {
        try {
            $payload = $this->request->getJSON(true) ?? [];
            $allowCurrentSession = (bool) ($payload['allow_current_session'] ?? false);

            $userId = (int) ($this->request->user->id ?? 0);
            $currentSessionId = $this->resolveCurrentSessionId();

            return $this->ok(
                'Oturumlar sonlandirildi',
                $this->sessionService->revokeAllSessions(
                    $userId,
                    $allowCurrentSession ? null : $currentSessionId
                )
            );
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    private function resolveCurrentSessionId(): ?int
    {
        /** @var object $request */
        $request = $this->request;
        if (! property_exists($request, 'session_id')) {
            return null;
        }

        $sessionId = $request->session_id;

        return $sessionId === null ? null : (int) $sessionId;
    }
}
