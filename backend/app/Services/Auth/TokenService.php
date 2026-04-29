<?php

namespace App\Services\Auth;

use App\Exceptions\UnauthorizedException;
use App\Libraries\Auth\JwtManager;
use Config\AuthConfig;
use UnexpectedValueException;

class TokenService
{
    public function __construct(
        private readonly JwtManager $jwtManager = new JwtManager(),
        private readonly AuthConfig $authConfig = new AuthConfig()
    ) {
    }

    public function issueAccessToken(int $userId, int $companyId, array $roles = [], ?int $sessionId = null): array
    {
        $claims = [
            'sub' => $userId,
            'company_id' => $companyId,
            'roles' => array_values($roles),
        ];
        if ($sessionId !== null) {
            $claims['sid'] = $sessionId;
        }

        $token = $this->jwtManager->issue($claims, $this->authConfig->accessTokenTtl);

        return [
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $this->authConfig->accessTokenTtl,
        ];
    }

    public function validateAccessToken(string $token): array
    {
        try {
            $payload = $this->jwtManager->decodeAndValidate($token);
        } catch (UnexpectedValueException $e) {
            if ($e->getMessage() === 'TOKEN_EXPIRED') {
                throw new UnauthorizedException('Token suresi dolmus', 'TOKEN_EXPIRED');
            }

            throw new UnauthorizedException('Token gecersiz', 'TOKEN_INVALID');
        }

        return [
            'user_id' => (int) ($payload['sub'] ?? 0),
            'company_id' => (int) ($payload['company_id'] ?? 0),
            'roles' => is_array($payload['roles'] ?? null) ? $payload['roles'] : [],
            'session_id' => isset($payload['sid']) ? (int) $payload['sid'] : null,
            'claims' => $payload,
        ];
    }
}
