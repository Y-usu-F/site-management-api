<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class UserRefreshTokenModel extends TenantAwareModel
{
    protected $table = 'user_refresh_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    protected $allowedFields = [
        'company_id',
        'user_id',
        'family_id',
        'token_hash',
        'token_jti',
        'expires_at',
        'issued_at',
        'last_used_at',
        'revoked_at',
        'revoked_reason',
        'revoked_by',
        'replaced_by_token_id',
        'created_ip',
        'created_user_agent',
        'device_name',
        'created_by',
        'updated_by',
    ];

    public function getActiveSessionsByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function findActiveTokenById(int $id): ?array
    {
        return $this->where('id', $id)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function revokeSession(int $userId, int $sessionId, ?int $revokedBy = null): bool
    {
        return $this->revokeSessionWithReason($userId, $sessionId, 'user_revoked_session', $revokedBy);
    }

    public function revokeSessionWithReason(int $userId, int $sessionId, string $reason, ?int $revokedBy = null): bool
    {
        return $this->builder()
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->update([
                'revoked_at' => date('Y-m-d H:i:s'),
                'revoked_reason' => $reason,
                'revoked_by' => $revokedBy,
            ]);
    }

    public function revokeAllSessions(int $userId, ?int $exceptSessionId = null): int
    {
        return $this->revokeAllSessionsWithReason(
            userId: $userId,
            reason: 'user_revoked_all_sessions',
            exceptSessionId: $exceptSessionId,
            revokedBy: $userId
        );
    }

    public function revokeAllSessionsWithReason(
        int $userId,
        string $reason,
        ?int $exceptSessionId = null,
        ?int $revokedBy = null
    ): int
    {
        $builder = $this->builder()
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'));

        if ($exceptSessionId !== null) {
            $builder->where('id !=', $exceptSessionId);
        }

        $builder->update([
            'revoked_at' => date('Y-m-d H:i:s'),
            'revoked_reason' => $reason,
            'revoked_by' => $revokedBy,
        ]);

        return $this->db->affectedRows();
    }

    public function touchLastUsedAt(int $id): bool
    {
        return $this->update($id, ['last_used_at' => date('Y-m-d H:i:s')]);
    }
}
