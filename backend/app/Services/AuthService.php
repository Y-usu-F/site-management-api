<?php

namespace App\Services;

use App\Services\Auth\AuthService as AuthOrchestratorService;

class AuthService
{
    public function __construct(
        private readonly AuthOrchestratorService $authOrchestratorService = new AuthOrchestratorService()
    )
    {}

    public function login(string $email, string $password): array
    {
        return $this->authOrchestratorService->login($email, $password);
    }

    public function me(): array
    {
        return $this->authOrchestratorService->me();
    }

    public function refresh(string $refreshToken): array
    {
        return $this->authOrchestratorService->refresh($refreshToken);
    }

    public function logout(): array
    {
        return $this->authOrchestratorService->logout();
    }
}
