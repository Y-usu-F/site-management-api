<?php

namespace App\Services\Auth;

use App\Exceptions\UnauthorizedException;
use App\Libraries\Auth\JwtManager;
use App\Models\UserRefreshTokenModel;
use Config\AuthConfig;
use DateTimeImmutable;
use Throwable;

class RefreshTokenService
{
    public function __construct(
        private readonly JwtManager $jwtManager = new JwtManager(),
        private readonly TokenService $tokenService = new TokenService(),
        private readonly AuthConfig $authConfig = new AuthConfig(),
        private readonly UserRefreshTokenModel $userRefreshTokenModel = new UserRefreshTokenModel()
    ) {
    }

    /**
     * Refresh token rotation + reuse detection uygular.
     *
     * @return array<string, mixed>
     */
    public function rotate(string $refreshToken): array
    {
        $now = date('Y-m-d H:i:s');
        $tokenHash = $this->hashToken($refreshToken);
        $tokenRow = $this->findByTokenHash($tokenHash);
        $payload = $this->decodeRefreshToken($refreshToken, $tokenRow);

        $familyId = (string) ($payload['fid'] ?? '');
        $userId = (int) ($payload['sub'] ?? 0);
        $companyId = (int) ($payload['company_id'] ?? 0);
        $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : [];

        if ($familyId === '' || $userId <= 0 || $companyId <= 0) {
            throw new UnauthorizedException('Refresh token gecersiz', 'TOKEN_INVALID');
        }

        if ($tokenRow === null) {
            throw new UnauthorizedException('Refresh token gecersiz', 'TOKEN_INVALID');
        }

        if ($tokenRow['revoked_at'] !== null) {
            $this->revokeFamilyById($familyId);
            throw new UnauthorizedException('Refresh token tekrar kullanildi', 'TOKEN_REUSED');
        }

        if ($this->isRowExpired($tokenRow['expires_at'])) {
            $this->revokeTokenRow((int) $tokenRow['id'], null, 'expired');
            throw new UnauthorizedException('Refresh token suresi dolmus', 'TOKEN_EXPIRED');
        }

        $this->userRefreshTokenModel->touchLastUsedAt((int) $tokenRow['id']);
        $newRefreshToken = $this->issue($userId, $companyId, $roles, $familyId);
        $newTokenHash = $this->hashToken($newRefreshToken);
        $newRow = $this->findByTokenHash($newTokenHash);
        if ($newRow !== null) {
            $this->userRefreshTokenModel->touchLastUsedAt((int) $newRow['id']);
        }

        $accessToken = $this->tokenService->issueAccessToken(
            $userId,
            $companyId,
            $roles,
            $newRow !== null ? (int) $newRow['id'] : null
        );

        $this->revokeTokenRow((int) $tokenRow['id'], $newRow !== null ? (int) $newRow['id'] : null, 'rotated');

        return array_merge($accessToken, [
            'refresh_token' => $newRefreshToken,
            'refresh_expires_in' => $this->authConfig->refreshTokenTtl,
        ]);
    }

    public function issue(int $userId, int $companyId, array $roles = [], ?string $familyId = null): string
    {
        $familyId = $familyId ?? bin2hex(random_bytes(16));
        $issuedAt = new DateTimeImmutable('now');
        $expiresAt = $issuedAt->modify('+' . $this->authConfig->refreshTokenTtl . ' seconds');

        $refreshToken = $this->jwtManager->issue([
            'sub' => $userId,
            'company_id' => $companyId,
            'roles' => array_values($roles),
            'typ' => 'refresh',
            'fid' => $familyId,
        ], $this->authConfig->refreshTokenTtl);

        $request = service('request');
        $refreshPayload = $this->jwtManager->decodeAndValidate($refreshToken);
        $deviceName = method_exists($request, 'getUserAgent')
            ? (string) $request->getUserAgent()->getBrowser() . ' on ' . (string) $request->getUserAgent()->getPlatform()
            : null;
        $this->userRefreshTokenModel->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'family_id' => $familyId,
            'token_hash' => $this->hashToken($refreshToken),
            'token_jti' => (string) ($refreshPayload['jti'] ?? ''),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'issued_at' => $issuedAt->format('Y-m-d H:i:s'),
            'last_used_at' => $issuedAt->format('Y-m-d H:i:s'),
            'revoked_at' => null,
            'revoked_reason' => null,
            'revoked_by' => null,
            'replaced_by_token_id' => null,
            'created_ip' => method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : null,
            'created_user_agent' => method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent()->getAgentString() : null,
            'device_name' => trim((string) $deviceName) !== ' on' ? $deviceName : null,
        ]);

        return $refreshToken;
    }

    public function revokeFamily(string $refreshToken): void
    {
        $tokenRow = $this->findByTokenHash($this->hashToken($refreshToken));
        if ($tokenRow !== null) {
            $this->revokeFamilyById((string) $tokenRow['family_id'], 'family_revoked');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRefreshToken(string $token, ?array $tokenRow = null): array
    {
        try {
            $payload = $this->jwtManager->decodeAndValidate($token);
        } catch (Throwable $e) {
            if ($e->getMessage() === 'TOKEN_EXPIRED') {
                if ($tokenRow !== null) {
                    $this->revokeTokenRow((int) $tokenRow['id'], null, 'expired');
                }
                throw new UnauthorizedException('Refresh token suresi dolmus', 'TOKEN_EXPIRED');
            }

            throw new UnauthorizedException('Refresh token gecersiz', 'TOKEN_INVALID');
        }

        if (($payload['typ'] ?? '') !== 'refresh') {
            throw new UnauthorizedException('Refresh token gecersiz', 'TOKEN_INVALID');
        }

        return $payload;
    }

    private function findByTokenHash(string $tokenHash): ?array
    {
        return $this->userRefreshTokenModel
            ->where('token_hash', $tokenHash)
            ->first();
    }

    private function revokeTokenRow(int $id, ?int $replacedByTokenId = null, ?string $reason = null): void
    {
        $data = ['revoked_at' => date('Y-m-d H:i:s')];
        if ($replacedByTokenId !== null) {
            $data['replaced_by_token_id'] = $replacedByTokenId;
        }
        if ($reason !== null) {
            $data['revoked_reason'] = $reason;
        }

        $this->userRefreshTokenModel->update($id, $data);
    }

    private function revokeFamilyById(string $familyId, string $reason = 'family_reused'): void
    {
        $now = date('Y-m-d H:i:s');
        $this->userRefreshTokenModel
            ->where('family_id', $familyId)
            ->where('revoked_at', null)
            ->set([
                'revoked_at' => $now,
                'revoked_reason' => $reason,
            ])
            ->update();
    }

    private function isRowExpired(string $expiresAt): bool
    {
        return strtotime($expiresAt) <= time();
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
