<?php

namespace App\Services\Profile;

use App\Core\BaseService;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationApiException;
use App\Models\UserModel;
use App\Models\UserRefreshTokenModel;
use App\Services\Auth\PasswordPolicyService;

class ChangePasswordService extends BaseService
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel(),
        private readonly UserRefreshTokenModel $userRefreshTokenModel = new UserRefreshTokenModel(),
        private readonly PasswordPolicyService $passwordPolicyService = new PasswordPolicyService()
    ) {
        parent::__construct();
    }

    /**
     * @return array{password_changed:bool, revoked_sessions:int}
     */
    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
        ?int $currentSessionId = null
    ): array {
        $user = $this->userModel->find($userId);
        if (! is_array($user)) {
            throw new UnauthorizedException('Kullanici bulunamadi', 'UNAUTHORIZED');
        }

        if (! password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            $this->audit('profile.password_change.failed', [
                'status' => 'failed',
                'target_user_id' => $userId,
                'entity_type' => 'user',
                'entity_id' => $userId,
                'meta' => ['reason' => 'current_password_invalid'],
            ]);
            throw new UnauthorizedException('Mevcut sifre hatali', 'UNAUTHORIZED');
        }

        if (password_verify($newPassword, (string) ($user['password_hash'] ?? ''))) {
            $this->audit('profile.password_change.failed', [
                'status' => 'failed',
                'target_user_id' => $userId,
                'entity_type' => 'user',
                'entity_id' => $userId,
                'meta' => ['reason' => 'same_password'],
            ]);
            throw new ValidationApiException('Yeni sifre eski sifreyle ayni olamaz', [
                'new_password' => ['Yeni sifre eski sifreyle ayni olamaz'],
            ]);
        }
        try {
            $this->passwordPolicyService->validateNewPassword($newPassword);
        } catch (ValidationApiException $e) {
            $this->audit('profile.password_change.failed', [
                'status' => 'failed',
                'target_user_id' => $userId,
                'entity_type' => 'user',
                'entity_id' => $userId,
                'meta' => ['reason' => 'policy_validation_failed'],
            ]);
            throw $e;
        }

        $this->userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        $revokedSessions = $this->userRefreshTokenModel->revokeAllSessionsWithReason(
            userId: $userId,
            reason: 'password_changed',
            exceptSessionId: $currentSessionId,
            revokedBy: $userId
        );

        $this->audit('profile.password_change.success', [
            'status' => 'success',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'meta' => [
                'revoked_sessions' => $revokedSessions,
                'current_session_kept' => $currentSessionId !== null,
            ],
        ]);

        return [
            'password_changed' => true,
            'revoked_sessions' => $revokedSessions,
        ];
    }
}
