<?php

namespace App\Services\Auth;

use App\Core\BaseService;
use App\Models\UserRefreshTokenModel;

class LogoutService extends BaseService
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService = new RefreshTokenService(),
        private readonly UserRefreshTokenModel $userRefreshTokenModel = new UserRefreshTokenModel()
    ) {
        parent::__construct();
    }

    /**
     * @return array{clear_refresh_cookie:bool, revoked:bool}
     */
    public function logout(?int $userId, ?int $sessionId, ?string $refreshToken): array
    {
        $revoked = false;

        if ($refreshToken !== null && trim($refreshToken) !== '' && substr_count($refreshToken, '.') === 2) {
            $this->refreshTokenService->revokeFamily($refreshToken);
            $revoked = true;
        } elseif ($userId !== null && $sessionId !== null) {
            $revoked = $this->userRefreshTokenModel->revokeSessionWithReason(
                userId: $userId,
                sessionId: $sessionId,
                reason: 'user_logout',
                revokedBy: $userId
            );
        }

        $this->audit('auth.logout.success', [
            'status' => 'success',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'meta' => [
                'revoked' => $revoked,
            ],
        ]);

        return [
            'clear_refresh_cookie' => true,
            'revoked' => $revoked,
        ];
    }
}
