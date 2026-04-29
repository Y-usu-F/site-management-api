<?php

namespace App\Database\Seeds;

use Config\PermissionCatalog;
use Throwable;

class RbacSeeder extends BaseAppSeeder
{
    /**
     * @var array<string,list<string>>
     */
    private array $rolePermissionMap = [
        'super_admin' => ['*'],
        'company_admin' => [
            'auth.me.view',
            'auth.logout',
            'auth.session.list',
            'auth.session.revoke',
            'auth.session.revoke.all',
            'profile.view',
            'profile.update',
            'profile.password.change',
            'user.role.assign',
            'user.role.revoke',
        ],
        'employee' => [
            'auth.me.view',
            'auth.logout',
            'profile.view',
            'profile.update',
            'profile.password.change',
        ],
    ];

    /**
     * @return array{roles:int,permissions:int,role_permissions:int}
     */
    public function run(): array
    {
        $name = static::class;
        $this->logStart($name);

        try {
            $roleCodes = [
                'super_admin'  => 'Super Admin',
                'company_admin' => 'Company Admin',
                'employee'     => 'Employee',
            ];

            $catalog = new PermissionCatalog();
            $catalogRows = $catalog->all();

            $roleIds = [];
            foreach ($roleCodes as $code => $label) {
                $roleIds[$code] = $this->upsertRole($code, $label);
            }

            $permissionIds = [];
            foreach ($catalogRows as $permission) {
                $code = (string) $permission['code'];
                $permissionIds[$code] = $this->upsertPermission($permission);
            }

            $this->deactivateCatalogExternalPermissions(array_keys($permissionIds));
            $resolvedRolePermissionMap = $this->resolveRolePermissionMap(array_keys($permissionIds));

            $linked = 0;
            foreach ($resolvedRolePermissionMap as $roleCode => $permissionList) {
                $roleId = $roleIds[$roleCode];

                foreach ($permissionList as $permissionCode) {
                    if (! isset($permissionIds[$permissionCode])) {
                        continue;
                    }

                    $permissionId = $permissionIds[$permissionCode];
                    $linked += $this->upsertRolePermission($roleId, $permissionId);
                }
            }

            $result = [
                'roles'            => count($roleIds),
                'permissions'      => count($permissionIds),
                'role_permissions' => $linked,
            ];

            $this->logSuccess($name, json_encode($result, JSON_UNESCAPED_SLASHES));

            return $result;
        } catch (Throwable $e) {
            $this->logFailure($name, $e->getMessage());
            throw $e;
        }
    }

    private function upsertRole(string $code, string $name): int
    {
        $builder = $this->db->table('roles');
        $existing = $builder
            ->where('company_id', null)
            ->where('code', $code)
            ->get()
            ->getRowArray();

        $now = $this->now();

        if ($existing !== null) {
            $builder->where('id', $existing['id'])->update([
                'name'       => $name,
                'updated_at' => $now,
            ]);

            return (int) $existing['id'];
        }

        $builder->insert([
            'company_id'  => null,
            'code'        => $code,
            'name'        => $name,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array{code:string,label:string,scope:string,description:string,is_active:bool} $permission
     */
    private function upsertPermission(array $permission): int
    {
        $builder = $this->db->table('permissions');
        $code = (string) $permission['code'];
        $existing = $builder->where('code', $code)->get()->getRowArray();
        $now = $this->now();

        if ($existing !== null) {
            $builder->where('id', $existing['id'])->update([
                'name'       => (string) $permission['label'],
                'scope'      => (string) $permission['scope'],
                'is_active'  => (bool) $permission['is_active'] ? 1 : 0,
                'deprecated_at' => null,
                'updated_at' => $now,
            ]);

            return (int) $existing['id'];
        }

        $builder->insert([
            'code'       => $code,
            'name'       => (string) $permission['label'],
            'scope'      => (string) $permission['scope'],
            'is_active'  => (bool) $permission['is_active'] ? 1 : 0,
            'deprecated_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param list<string> $catalogCodes
     */
    private function deactivateCatalogExternalPermissions(array $catalogCodes): void
    {
        $rows = $this->db->table('permissions')
            ->select('id, code')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        $catalogLookup = array_fill_keys($catalogCodes, true);
        $now = $this->now();

        foreach ($rows as $row) {
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            if ($code === '' || isset($catalogLookup[$code])) {
                continue;
            }

            $this->db->table('permissions')
                ->where('id', (int) $row['id'])
                ->update([
                    'is_active' => 0,
                    'deprecated_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * @param list<string> $catalogCodes
     * @return array<string,list<string>>
     */
    private function resolveRolePermissionMap(array $catalogCodes): array
    {
        $allCompanyScopeCodes = [];
        $catalog = new PermissionCatalog();
        foreach ($catalog->all() as $permission) {
            if (($permission['scope'] ?? 'company') !== 'company') {
                continue;
            }
            $allCompanyScopeCodes[] = (string) $permission['code'];
        }

        $map = $this->rolePermissionMap;
        if (($map['super_admin'] ?? []) === ['*']) {
            $map['super_admin'] = $catalogCodes;
        }

        // Company admin must never receive system-scoped permissions.
        $map['company_admin'] = array_values(array_intersect($map['company_admin'] ?? [], $allCompanyScopeCodes));

        return $map;
    }

    private function upsertRolePermission(int $roleId, int $permissionId): int
    {
        $builder = $this->db->table('role_permissions');
        $existing = $builder
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            $builder->where('id', (int) $existing['id'])->update([
                'is_active' => 1,
                'deleted_at' => null,
                'updated_at' => $this->now(),
            ]);
            return 0;
        }

        $now = $this->now();
        $builder->insert([
            'role_id'      => $roleId,
            'permission_id'=> $permissionId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        return 1;
    }
}
