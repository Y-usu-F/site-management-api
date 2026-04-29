<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class UserRoleModel extends TenantAwareModel
{
    protected $table = 'user_roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id',
        'user_id',
        'role_id',
        'is_active',
        'role_version',
        'created_by',
        'updated_by',
    ];

    /**
     * @return list<array{id:int,role_id:int,code:string,name:string,role_version:int}>
     */
    public function getActiveRolesForUser(int $userId, int $companyId): array
    {
        $companyId = $this->guardCompanyId($companyId);
        $rows = $this->db->table('user_roles ur')
            ->select('ur.id, ur.role_id, ur.role_version, r.code, r.name')
            ->join('roles r', 'r.id = ur.role_id', 'inner')
            ->where('ur.user_id', $userId)
            ->where('ur.company_id', $companyId)
            ->where('ur.is_active', 1)
            ->where('ur.deleted_at', null)
            ->where('r.deleted_at', null)
            ->orderBy('r.code', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'role_id' => (int) $row['role_id'],
            'code' => (string) $row['code'],
            'name' => (string) $row['name'],
            'role_version' => (int) ($row['role_version'] ?? 1),
        ], $rows);
    }

    public function getRoleVersionForUser(int $userId, int $companyId): int
    {
        $companyId = $this->guardCompanyId($companyId);
        $row = $this->db->table('user_roles')
            ->selectMax('role_version', 'max_role_version')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($row === null || ! isset($row['max_role_version'])) {
            return 0;
        }

        return (int) $row['max_role_version'];
    }

    public function assignRoleToUser(int $userId, int $companyId, int $roleId): int
    {
        $companyId = $this->guardCompanyId($companyId);
        $newVersion = $this->getRoleVersionForUser($userId, $companyId) + 1;
        $existing = $this->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('role_id', $roleId)
            ->where('deleted_at', null)
            ->first();

        if (is_array($existing)) {
            $this->update((int) $existing['id'], [
                'is_active' => 1,
                'role_version' => $newVersion,
            ]);

            return $newVersion;
        }

        $this->insert([
            'user_id' => $userId,
            'company_id' => $companyId,
            'role_id' => $roleId,
            'is_active' => 1,
            'role_version' => $newVersion,
        ]);

        return $newVersion;
    }

    public function revokeRoleFromUser(int $userId, int $companyId, int $roleId): int
    {
        $companyId = $this->guardCompanyId($companyId);
        $newVersion = $this->getRoleVersionForUser($userId, $companyId) + 1;

        $this->builder()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('role_id', $roleId)
            ->where('deleted_at', null)
            ->update([
                'is_active' => 0,
                'role_version' => $newVersion,
            ]);

        return $newVersion;
    }

    public function bumpRoleVersionForUserCompany(int $userId, int $companyId): int
    {
        $companyId = $this->guardCompanyId($companyId);
        $newVersion = $this->getRoleVersionForUser($userId, $companyId) + 1;

        $this->builder()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('deleted_at', null)
            ->update(['role_version' => $newVersion]);

        return $newVersion;
    }

    /**
     * @param list<int> $roleIds
     * @return list<array{user_id:int,company_id:int}>
     */
    public function getActiveUserCompanyPairsByRoleIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $rows = $this->db->table('user_roles')
            ->select('user_id, company_id')
            ->whereIn('role_id', $roleIds)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->groupBy('user_id, company_id')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'user_id' => (int) $row['user_id'],
            'company_id' => (int) $row['company_id'],
        ], $rows);
    }
}

