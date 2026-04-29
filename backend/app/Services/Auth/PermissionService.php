<?php

namespace App\Services\Auth;

use App\Exceptions\PermissionNotFoundException;
use App\Models\PermissionModel;
use Config\PermissionCatalog;

class PermissionService
{
    public function __construct(
        private readonly RoleService $roleService = new RoleService(),
        private readonly PermissionModel $permissionModel = new PermissionModel(),
        private readonly PermissionCatalog $permissionCatalog = new PermissionCatalog(),
        private readonly PermissionCacheService $permissionCacheService = new PermissionCacheService()
    ) {
    }

    /**
     * @return list<array{code:string,scope:string,is_active:int|string}>
     */
    public function getPermissionsForUser(int $userId, int $companyId): array
    {
        $roles = $this->roleService->getActiveRolesForUser($userId, $companyId);
        $roleVersion = $this->roleService->getRoleVersionForUser($userId, $companyId);
        $roleIds = array_values(array_unique(array_map(static fn (array $role): int => (int) $role['role_id'], $roles)));

        return $this->permissionCacheService->rememberPermissions(
            $userId,
            $companyId,
            $roleVersion,
            fn (): array => $this->resolvePermissionsForRoleIds($roleIds)
        );
    }

    /**
     * @return list<string>
     */
    public function getPermissionCodesForUser(int $userId, int $companyId): array
    {
        $permissions = $this->getPermissionsForUser($userId, $companyId);
        return array_values(array_map(static fn (array $permission): string => $permission['code'], $permissions));
    }

    public function userHasPermission(int $userId, int $companyId, string $permissionCode): bool
    {
        $normalized = strtolower(trim($permissionCode));
        return in_array($normalized, $this->getPermissionCodesForUser($userId, $companyId), true);
    }

    /**
     * @param list<int> $roleIds
     * @return list<array{code:string,scope:string,is_active:int|string}>
     */
    private function resolvePermissionsForRoleIds(array $roleIds): array
    {
        $rows = $this->permissionModel->getActivePermissionsForRoleIds($roleIds);
        $byCode = [];

        foreach ($rows as $row) {
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            if ($code === '') {
                continue;
            }

            if (! $this->permissionCatalog->exists($code)) {
                throw new PermissionNotFoundException('Permission catalog disi kod tespit edildi: ' . $code);
            }

            $catalogRow = $this->permissionCatalog->get($code);
            if (! (bool) $catalogRow['is_active']) {
                continue;
            }

            $byCode[$code] = [
                'code' => $code,
                'scope' => (string) $catalogRow['scope'],
                'is_active' => 1,
            ];
        }

        ksort($byCode, SORT_STRING);
        return array_values($byCode);
    }
}

