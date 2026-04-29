<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'code',
        'name',
        'scope',
        'is_active',
        'deprecated_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @param list<int> $roleIds
     * @return list<array{code:string,scope:string,is_active:int|string}>
     */
    public function getActivePermissionsForRoleIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $rows = $this->db->table('role_permissions rp')
            ->select('p.code, p.scope, p.is_active')
            ->join('permissions p', 'p.id = rp.permission_id', 'inner')
            ->whereIn('rp.role_id', $roleIds)
            ->where('rp.is_active', 1)
            ->where('p.is_active', 1)
            ->where('p.deprecated_at', null)
            ->where('rp.deleted_at', null)
            ->where('p.deleted_at', null)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'code' => (string) $row['code'],
            'scope' => (string) $row['scope'],
            'is_active' => $row['is_active'] ?? 0,
        ], $rows);
    }

    /**
     * @return list<array{code:string,is_active:int|string,deprecated_at:?string}>
     */
    public function getAllPermissionsForMatrix(): array
    {
        $rows = $this->db->table('permissions')
            ->select('code, is_active, deprecated_at')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'code' => (string) ($row['code'] ?? ''),
            'is_active' => $row['is_active'] ?? 0,
            'deprecated_at' => isset($row['deprecated_at']) ? (string) $row['deprecated_at'] : null,
        ], $rows);
    }

    /**
     * @return list<array{
     *   role_id:int|string,
     *   permission_id:int|string,
     *   role_permission_active:int|string,
     *   permission_code:?string,
     *   permission_active:int|string,
     *   permission_deprecated_at:?string
     * }>
     */
    public function getRolePermissionMatrixRows(): array
    {
        return $this->db->table('role_permissions rp')
            ->select('rp.role_id, rp.permission_id, rp.is_active AS role_permission_active, p.code AS permission_code, p.is_active AS permission_active, p.deprecated_at AS permission_deprecated_at')
            ->join('permissions p', 'p.id = rp.permission_id', 'left')
            ->where('rp.deleted_at', null)
            ->get()
            ->getResultArray();
    }
}

