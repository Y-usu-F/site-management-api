<?php

namespace App\Policies;

use App\Services\Auth\AuthorizationService;

class AuthorizationPolicy
{
    public function __construct(
        private readonly AuthorizationService $authorizationService = new AuthorizationService()
    ) {
    }

    /**
     * @return array{allowed:bool,reason:?string,permission:string,scope:string,is_super_admin:bool}
     */
    public function authorize(
        int $userId,
        int $companyId,
        string $permissionCode,
        ?int $targetCompanyId = null
    ): array {
        return $this->authorizationService->authorize($userId, $companyId, $permissionCode, $targetCompanyId);
    }

    public function can(
        int $userId,
        int $companyId,
        string $permissionCode,
        ?int $targetCompanyId = null
    ): bool {
        return $this->authorize($userId, $companyId, $permissionCode, $targetCompanyId)['allowed'];
    }

    public function denyReason(
        int $userId,
        int $companyId,
        string $permissionCode,
        ?int $targetCompanyId = null
    ): ?string {
        return $this->authorize($userId, $companyId, $permissionCode, $targetCompanyId)['reason'];
    }
}

