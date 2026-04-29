<?php

namespace App\Policies;

/**
 * @deprecated RBAC-514 sonrasi yeni kodda AuthorizationPolicy kullanin.
 *             Bu sinif backward compatibility icin korunur.
 */
class RbacPolicy
{
    /**
     * @var array<string, list<string>>
     */
    private array $permissionRoleMap = [
        'auth.me.read' => ['super_admin', 'company_admin', 'employee'],
        'auth.session.refresh' => ['super_admin', 'company_admin', 'employee'],
        'auth.session.logout' => ['super_admin', 'company_admin', 'employee'],
        'auth.session.list' => ['super_admin', 'company_admin', 'employee'],
        'auth.session.revoke' => ['super_admin', 'company_admin', 'employee'],
        'auth.session.revoke_all' => ['super_admin', 'company_admin', 'employee'],
    ];

    /**
     * @param list<string> $roles
     */
    public function allows(string $permission, array $roles): bool
    {
        if (in_array('super_admin', $roles, true)) {
            return true;
        }

        $allowedRoles = $this->permissionRoleMap[$permission] ?? [];
        foreach ($roles as $role) {
            if (in_array($role, $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
