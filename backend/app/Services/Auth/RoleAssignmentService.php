<?php

namespace App\Services\Auth;

use App\Models\UserRoleModel;
use App\Services\Common\AuditLogService;
use Throwable;

class RoleAssignmentService
{
    public function __construct(
        private readonly UserRoleModel $userRoleModel = new UserRoleModel(),
        private readonly PermissionCacheService $permissionCacheService = new PermissionCacheService(),
        private readonly AuditLogService $auditLogService = new AuditLogService()
    ) {
    }

    public function assignRole(int $userId, int $companyId, int $roleId, ?int $actorUserId = null): int
    {
        $newVersion = $this->userRoleModel->assignRoleToUser($userId, $companyId, $roleId);
        $this->safeInvalidate($userId, $companyId);
        $this->safeAudit('rbac.role.assigned', $userId, $companyId, $roleId, $actorUserId);

        return $newVersion;
    }

    public function revokeRole(int $userId, int $companyId, int $roleId, ?int $actorUserId = null): int
    {
        $newVersion = $this->userRoleModel->revokeRoleFromUser($userId, $companyId, $roleId);
        $this->safeInvalidate($userId, $companyId);
        $this->safeAudit('rbac.role.revoked', $userId, $companyId, $roleId, $actorUserId);

        return $newVersion;
    }

    /**
     * @param list<int> $roleIds
     */
    public function invalidateUsersByRoleIds(array $roleIds): void
    {
        $targets = $this->userRoleModel->getActiveUserCompanyPairsByRoleIds($roleIds);
        foreach ($targets as $target) {
            $this->userRoleModel->bumpRoleVersionForUserCompany($target['user_id'], $target['company_id']);
            $this->safeInvalidate($target['user_id'], $target['company_id']);
        }
    }

    private function safeInvalidate(int $userId, int $companyId): void
    {
        try {
            $this->permissionCacheService->invalidateUserCompany($userId, $companyId);
        } catch (Throwable $e) {
            // Fail-open: invalidation patlasa da role update akisini bozma.
            log_message('error', 'Role assignment invalidate failed: {message}', ['message' => $e->getMessage()]);
        }
    }

    private function safeAudit(
        string $event,
        int $targetUserId,
        int $companyId,
        int $roleId,
        ?int $actorUserId
    ): void {
        try {
            $this->auditLogService->recordEvent($event, [
                'actor_user_id' => $actorUserId ?? $targetUserId,
                'target_user_id' => $targetUserId,
                'company_id' => $companyId,
                'role_id' => $roleId,
                'status' => 'success',
                'entity_type' => 'user_role',
                'entity_id' => $targetUserId,
                'meta' => [
                    'company_id' => $companyId,
                    'role_id' => $roleId,
                    'target_user_id' => $targetUserId,
                ],
            ]);
        } catch (Throwable $e) {
            // Fail-safe: audit hatasi role assignment akisini bozmamali.
            log_message('error', 'Role assignment audit failed: {message}', ['message' => $e->getMessage()]);
        }
    }
}

