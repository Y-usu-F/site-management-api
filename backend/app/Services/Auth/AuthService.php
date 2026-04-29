<?php

namespace App\Services\Auth;

use App\Core\BaseService;
use App\Exceptions\ValidationApiException;
use App\Exceptions\UnauthorizedException;
use App\Models\PasswordResetTokenModel;
use App\Models\UserModel;
use App\Models\UserRefreshTokenModel;
use Config\AuthConfig;

class AuthService extends BaseService
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel(),
        private readonly PasswordResetTokenModel $passwordResetTokenModel = new PasswordResetTokenModel(),
        private readonly UserRefreshTokenModel $userRefreshTokenModel = new UserRefreshTokenModel(),
        private readonly TokenService $tokenService = new TokenService(),
        private readonly RefreshTokenService $refreshTokenService = new RefreshTokenService(),
        private readonly LogoutService $logoutService = new LogoutService(),
        private readonly PasswordPolicyService $passwordPolicyService = new PasswordPolicyService(),
        private readonly AuthConfig $authConfig = new AuthConfig()
    ) {
        parent::__construct();
    }

    public function login(string $identity, string $password): array
    {
        $user = $this->userModel->findForLogin($identity);
        if ($user === null) {
            $this->audit('auth.login.failed', [
                'status' => 'failed',
                'meta' => ['reason' => 'user_not_found', 'identity' => $identity],
            ]);
            throw new UnauthorizedException('Email veya sifre hatali', 'UNAUTHORIZED');
        }

        if (! $this->isUserActive($user)) {
            $this->audit('auth.login.blocked_inactive_user', [
                'status' => 'failed',
                'target_user_id' => (int) $user['id'],
                'meta' => ['reason' => 'inactive_user'],
            ]);
            throw new UnauthorizedException('Kullanici aktif degil', 'UNAUTHORIZED');
        }

        if ($this->passwordPolicyService->isLocked($user['locked_until'] ?? null)) {
            throw new UnauthorizedException('Hesap gecici olarak kilitlendi', 'UNAUTHORIZED');
        }

        if (! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $this->handleFailedLogin((int) $user['id'], (int) ($user['failed_login_count'] ?? 0));
            throw new UnauthorizedException('Email veya sifre hatali', 'UNAUTHORIZED');
        }

        $companyId = (int) ($user['company_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        $roles = $this->userModel->getRoleCodes($userId, $companyId);

        $refreshToken = $this->refreshTokenService->issue($userId, $companyId, $roles);
        $refreshTokenId = $this->userModel->findLatestRefreshTokenId($userId, $companyId);
        $accessToken = $this->tokenService->issueAccessToken($userId, $companyId, $roles, $refreshTokenId);

        $this->userModel->markLoginSuccess($userId);
        $this->audit('auth.login.success', [
            'status' => 'success',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
        ]);

        return array_merge($accessToken, [
            'refresh_token' => $refreshToken,
            'refresh_expires_in' => $this->authConfig->refreshTokenTtl,
            'user' => [
                'id' => $userId,
                'company_id' => $companyId,
                'email' => (string) ($user['email'] ?? ''),
                'roles' => $roles,
            ],
        ]);
    }

    public function me(): array
    {
        $request = service('request');
        $userId = (int) ($request->user?->id ?? 0);
        $companyId = (int) ($request->company_id ?? 0);
        $roles = is_array($request->roles ?? null) ? $request->roles : [];

        if ($userId <= 0) {
            throw new UnauthorizedException('Kimlik dogrulama gerekli', 'UNAUTHORIZED');
        }

        $user = $this->userModel->find($userId);
        if ($user === null || ! $this->isUserActive($user)) {
            throw new UnauthorizedException('Kullanici bulunamadi veya pasif', 'UNAUTHORIZED');
        }

        return [
            'id' => $userId,
            'company_id' => $companyId,
            'email' => (string) ($user['email'] ?? ''),
            'roles' => $roles,
            'permissions' => [],
        ];
    }

    public function refresh(string $refreshToken): array
    {
        if (substr_count($refreshToken, '.') !== 2) {
            $this->audit('auth.refresh.failed', [
                'status' => 'failed',
                'meta' => ['error_code' => 'TOKEN_INVALID'],
            ]);
            throw new UnauthorizedException('Refresh token gecersiz', 'TOKEN_INVALID');
        }

        try {
            $result = $this->refreshTokenService->rotate($refreshToken);
            $this->audit('auth.refresh.success', ['status' => 'success']);

            return $result;
        } catch (UnauthorizedException $e) {
            $event = $e->getErrorCode() === 'TOKEN_REUSED'
                ? 'auth.refresh.reuse_detected'
                : 'auth.refresh.failed';
            $this->audit($event, [
                'status' => 'failed',
                'meta' => ['error_code' => $e->getErrorCode()],
            ]);
            throw $e;
        }
    }

    public function forgotPassword(string $email): array
    {
        $normalizedEmail = strtolower(trim($email));
        $user = $this->userModel->findForLogin($normalizedEmail);
        $request = service('request');

        if (is_array($user)) {
            $plainToken = bin2hex(random_bytes(32));
            $this->passwordResetTokenModel->insert([
                'user_id' => (int) $user['id'],
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'used_at' => null,
                'requested_ip' => $request->getIPAddress(),
                'requested_user_agent' => $request->getUserAgent()->getAgentString(),
            ]);
        }

        $this->audit('auth.forgot_password.requested', [
            'status' => 'success',
            'target_user_id' => is_array($user) ? (int) $user['id'] : null,
            'meta' => [
                'email' => $normalizedEmail,
                'user_found' => is_array($user),
            ],
        ]);

        return [
            'accepted' => true,
            'delivery' => 'if_account_exists',
        ];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        $tokenRow = $this->passwordResetTokenModel
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! is_array($tokenRow)) {
            $this->auditResetFailure('token_invalid');
            throw new UnauthorizedException('Reset token gecersiz', 'TOKEN_INVALID');
        }

        if (! empty($tokenRow['used_at'])) {
            $this->auditResetFailure('token_used', (int) $tokenRow['user_id']);
            throw new UnauthorizedException('Reset token zaten kullanilmis', 'TOKEN_ALREADY_USED');
        }

        if (strtotime((string) $tokenRow['expires_at']) <= time()) {
            $this->auditResetFailure('token_expired', (int) $tokenRow['user_id']);
            throw new UnauthorizedException('Reset token suresi dolmus', 'TOKEN_EXPIRED');
        }

        $userId = (int) $tokenRow['user_id'];
        $user = $this->userModel->find($userId);
        if (! is_array($user)) {
            $this->auditResetFailure('user_not_found', $userId);
            throw new UnauthorizedException('Reset token gecersiz', 'TOKEN_INVALID');
        }

        if (password_verify($newPassword, (string) ($user['password_hash'] ?? ''))) {
            $this->auditResetFailure('same_password', $userId);
            throw new ValidationApiException('Yeni sifre eski sifreyle ayni olamaz', [
                'password' => ['Yeni sifre eski sifreyle ayni olamaz'],
            ]);
        }

        $this->passwordPolicyService->validateNewPassword($newPassword);
        $this->userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        $this->passwordResetTokenModel->update((int) $tokenRow['id'], ['used_at' => date('Y-m-d H:i:s')]);

        $revokedSessions = $this->userRefreshTokenModel->revokeAllSessionsWithReason(
            userId: $userId,
            reason: 'password_reset',
            exceptSessionId: null,
            revokedBy: $userId
        );

        $this->audit('auth.reset_password.success', [
            'status' => 'success',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'meta' => ['revoked_sessions' => $revokedSessions],
        ]);

        return [
            'password_reset' => true,
            'revoked_sessions' => $revokedSessions,
        ];
    }

    /**
     * @return array{clear_refresh_cookie:bool, revoked:bool}
     */
    public function logout(?string $refreshToken = null): array
    {
        $request = service('request');
        $payload = $request->getJSON(true);
        $tokenFromBody = is_array($payload) ? (string) ($payload['refresh_token'] ?? '') : '';
        $resolvedToken = $refreshToken ?? $tokenFromBody;

        $sessionId = property_exists($request, 'session_id') ? (int) $request->session_id : null;

        return $this->logoutService->logout(
            userId: isset($request->user?->id) ? (int) $request->user->id : null,
            sessionId: $sessionId,
            refreshToken: $resolvedToken !== '' ? $resolvedToken : null
        );
    }

    private function handleFailedLogin(int $userId, int $currentFailedCount): void
    {
        $newCount = $currentFailedCount + 1;
        $lockUntil = null;

        if ($newCount >= $this->passwordPolicyService->maxFailedLoginAttempts()) {
            $lockUntil = date('Y-m-d H:i:s', time() + $this->passwordPolicyService->lockDurationSeconds());
        }

        $this->userModel->markLoginFailed($userId, $newCount, $lockUntil);
        $this->audit('auth.login.failed', [
            'status' => 'failed',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'meta' => [
                'failed_login_count' => $newCount,
                'locked_until' => $lockUntil,
            ],
        ]);
    }

    private function isUserActive(array $user): bool
    {
        $status = strtolower((string) ($user['status'] ?? 'active'));
        $isActive = isset($user['is_active']) ? (int) $user['is_active'] === 1 : true;

        return $status === 'active' && $isActive;
    }

    private function auditResetFailure(string $reason, ?int $userId = null): void
    {
        $this->audit('auth.reset_password.failed', [
            'status' => 'failed',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'meta' => ['reason' => $reason],
        ]);
    }
}
