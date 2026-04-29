<?php

namespace App\Services\Auth;

use App\Exceptions\AuthorizationException;
use Config\PermissionCatalog;
use Config\TenantConfig;

class AuthorizationService
{
    public function __construct(
        private readonly PermissionService $permissionService = new PermissionService(),
        private readonly RoleService $roleService = new RoleService(),
        private readonly PermissionCatalog $permissionCatalog = new PermissionCatalog(),
        private readonly TenantConfig $tenantConfig = new TenantConfig()
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
        $normalizedPermission = strtolower(trim($permissionCode));
        $this->permissionCatalog->assertExists($normalizedPermission);
        $scope = $this->permissionCatalog->scopeOf($normalizedPermission);

        $roles = $this->roleService->getRoleCodesForUser($userId, $companyId);
        $isSuperAdmin = in_array($this->tenantConfig->superAdminRole, $roles, true);

        if ($isSuperAdmin) {
            return $this->decision(true, 'super_admin_override', $normalizedPermission, $scope, true);
        }

        $hasPermission = $this->permissionService->userHasPermission($userId, $companyId, $normalizedPermission);
        if (! $hasPermission) {
            return $this->decision(false, 'permission_missing', $normalizedPermission, $scope, false);
        }

        if ($scope === 'system') {
            return $this->decision(false, 'system_scope_requires_super_admin', $normalizedPermission, $scope, false);
        }

        $resolvedTargetCompanyId = $targetCompanyId ?? $companyId;
        if ($resolvedTargetCompanyId !== $companyId) {
            return $this->decision(false, 'tenant_mismatch', $normalizedPermission, $scope, false);
        }

        return $this->decision(true, null, $normalizedPermission, $scope, false);
    }

    public function ensureAuthorized(
        int $userId,
        int $companyId,
        string $permissionCode,
        ?int $targetCompanyId = null
    ): void {
        $decision = $this->authorize($userId, $companyId, $permissionCode, $targetCompanyId);
        if (! $decision['allowed']) {
            throw new AuthorizationException('Bu islem icin yetkiniz yok', $decision);
        }
    }

    /**
     * @return array{allowed:bool,reason:?string,permission:string,scope:string,is_super_admin:bool}
     */
    private function decision(
        bool $allowed,
        ?string $reason,
        string $permission,
        string $scope,
        bool $isSuperAdmin
    ): array {
        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'permission' => $permission,
            'scope' => $scope,
            'is_super_admin' => $isSuperAdmin,
        ];
    }
}

