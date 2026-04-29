<?php

namespace App\Services;

use App\Services\Auth\TokenService;

class JwtService
{
    public function __construct(private readonly TokenService $tokenService = new TokenService())
    {
    }

    public function issueAccessToken(int $userId, int $companyId, array $roles = []): array
    {
        return $this->tokenService->issueAccessToken($userId, $companyId, $roles);
    }
}
