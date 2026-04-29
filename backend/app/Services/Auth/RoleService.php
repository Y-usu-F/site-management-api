<?php

namespace App\Services\Auth;

use App\Models\UserRoleModel;

class RoleService
{
    public function __construct(
        private readonly UserRoleModel $userRoleModel = new UserRoleModel()
    ) {
    }

    /**
     * @return list<array{id:int,role_id:int,code:string,name:string,role_version:int}>
     */
    public function getActiveRolesForUser(int $userId, int $companyId): array
    {
        return $this->userRoleModel->getActiveRolesForUser($userId, $companyId);
    }

    /**
     * @return list<string>
     */
    public function getRoleCodesForUser(int $userId, int $companyId): array
    {
        $roles = $this->getActiveRolesForUser($userId, $companyId);
        $codes = array_map(static fn (array $role): string => $role['code'], $roles);
        $codes = array_values(array_unique($codes));
        sort($codes, SORT_STRING);

        return $codes;
    }

    public function getRoleVersionForUser(int $userId, int $companyId): int
    {
        return $this->userRoleModel->getRoleVersionForUser($userId, $companyId);
    }
}

