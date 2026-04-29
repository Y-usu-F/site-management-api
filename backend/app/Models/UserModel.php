<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class UserModel extends TenantAwareModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'company_id',
        'public_id',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'status',
        'is_active',
        'password_changed_at',
        'last_login_at',
        'failed_login_count',
        'locked_until',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps = true;

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmailGlobal(string $email, ?int $exceptUserId = null, bool $includeDeleted = true): ?array
    {
        $builder = $includeDeleted ? $this->withDeleted() : $this;
        $builder = $builder->where('email', strtolower(trim($email)));
        if ($exceptUserId !== null) {
            $builder = $builder->where('id !=', $exceptUserId);
        }

        $row = $builder->first();

        return is_array($row) ? $row : null;
    }

    public function setActiveState(int $userId, bool $active): bool
    {
        return $this->update($userId, [
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function findForLogin(string $identity): ?array
    {
        return $this->where('email', strtolower(trim($identity)))->first();
    }

    /**
     * @return list<string>
     */
    public function getRoleCodes(int $userId, int $companyId): array
    {
        $rows = $this->db->table('user_roles ur')
            ->select('r.code')
            ->join('roles r', 'r.id = ur.role_id', 'inner')
            ->where('ur.user_id', $userId)
            ->where('ur.company_id', $companyId)
            ->get()
            ->getResultArray();

        return array_values(array_map(static fn (array $row): string => (string) ($row['code'] ?? ''), $rows));
    }

    public function markLoginFailed(int $userId, int $failedCount, ?string $lockedUntil): bool
    {
        return $this->update($userId, [
            'failed_login_count' => $failedCount,
            'locked_until' => $lockedUntil,
        ]);
    }

    public function markLoginSuccess(int $userId): bool
    {
        return $this->update($userId, [
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findLatestRefreshTokenId(int $userId, int $companyId): ?int
    {
        $row = $this->db->table('user_refresh_tokens')
            ->select('id')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        return $this->update($userId, [
            'password_hash' => $passwordHash,
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateProfileByWhitelist(int $userId, array $data): bool
    {
        $allowed = ['first_name', 'last_name'];
        $safeData = array_intersect_key($data, array_flip($allowed));
        if ($safeData === []) {
            return true;
        }

        return $this->update($userId, $safeData);
    }

    public function getSafeProfile(int $userId): ?array
    {
        $row = $this->select([
            'id',
            'company_id',
            'email',
            'first_name',
            'last_name',
            'last_login_at',
            'created_at',
            'updated_at',
        ])->find($userId);

        return is_array($row) ? $row : null;
    }
}
