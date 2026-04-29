<?php

namespace App\Services\Auth;

use App\Exceptions\UnauthorizedException;
use App\Models\UserRefreshTokenModel;

class SessionService
{
    public function __construct(
        private readonly UserRefreshTokenModel $userRefreshTokenModel = new UserRefreshTokenModel()
    ) {
    }

    public function listSessions(int $userId, ?int $currentTokenId = null): array
    {
        $rows = $this->userRefreshTokenModel->getActiveSessionsByUser($userId);

        return array_map(static function (array $row) use ($currentTokenId): array {
            return [
                'id' => (int) $row['id'],
                'device_name' => $row['device_name'] ?? null,
                'ip' => $row['created_ip'] ?? null,
                'user_agent' => $row['created_user_agent'] ?? null,
                'created_at' => $row['created_at'] ?? null,
                'last_used_at' => $row['last_used_at'] ?? null,
                'expires_at' => $row['expires_at'] ?? null,
                'current_session' => $currentTokenId !== null && (int) $row['id'] === $currentTokenId,
            ];
        }, $rows);
    }

    public function revokeSession(int $userId, int $sessionId): array
    {
        $session = $this->userRefreshTokenModel->findActiveTokenById($sessionId);
        if ($session === null || (int) $session['user_id'] !== $userId) {
            throw new UnauthorizedException('Bu oturumu sonlandirma yetkiniz yok', 'TOKEN_INVALID');
        }

        $ok = $this->userRefreshTokenModel->revokeSession($userId, $sessionId, $userId);
        if (! $ok) {
            throw new UnauthorizedException('Oturum sonlandirilamadi', 'TOKEN_INVALID');
        }

        return [
            'revoked' => true,
            'session_id' => $sessionId,
        ];
    }

    public function revokeAllSessions(int $userId, ?int $currentTokenId = null): array
    {
        $affected = $this->userRefreshTokenModel->revokeAllSessions($userId, $currentTokenId);

        return [
            'revoked' => true,
            'affected' => $affected,
            'excluded_session_id' => $currentTokenId,
        ];
    }
}
