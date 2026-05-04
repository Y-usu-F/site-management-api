<?php

namespace App\Core;

use App\Exceptions\TenantAccessDeniedException;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\Model;
use Config\TenantConfig;

abstract class TenantAwareModel extends Model
{
    protected bool $requiresTenant = true;
    protected bool $enforceTenantGuard = true;

    protected function initialize(): void
    {
        parent::initialize();
        $this->beforeFind = array_values(array_unique(array_merge($this->beforeFind, ['applyTenantScopeCallback'])));
        $this->beforeInsert = array_values(array_unique(array_merge($this->beforeInsert, ['applyTenantInsertCallback'])));
        $this->beforeUpdate = array_values(array_unique(array_merge($this->beforeUpdate, ['applyTenantUpdateCallback'])));
    }

    public function builder(?string $table = null): BaseBuilder
    {
        $builder = parent::builder($table);
        $this->applyTenantScopeToBuilder($builder, $table);
        return $builder;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function applyTenantScopeCallback(array $data): array
    {
        if (! $this->shouldEnforceTenant()) {
            return $data;
        }

        $this->resolveContextCompanyId();
        return $data;
    }

    protected function applyTenantScope(): static
    {
        if (! $this->shouldEnforceTenant()) {
            return $this;
        }

        $companyId = $this->resolveContextCompanyId();
        return $this->where($this->table . '.company_id', $companyId);
    }

    public function tenantFind(int|string $id): array|object|null
    {
        return $this->applyTenantScope()->find($id);
    }

    protected function applyTenantInsertCallback(array $data): array
    {
        $request = service('request');
        $contextCompanyId = $this->resolveContextCompanyId(false);
        $payloadCompanyId = isset($data['data']['company_id']) ? (int) $data['data']['company_id'] : null;
        $userId = $request->user?->id ?? null;

        if ($this->requiresTenant) {
            $companyId = $contextCompanyId;
            if ($companyId === null && $payloadCompanyId !== null && $payloadCompanyId > 0) {
                $companyId = $payloadCompanyId;
            }

            if ($companyId === null) {
                throw new TenantAccessDeniedException('Tenant baglami bulunamadi');
            }

            if ($contextCompanyId !== null && $payloadCompanyId !== null && $payloadCompanyId > 0 && $payloadCompanyId !== $contextCompanyId) {
                throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
            }

            $data['data']['company_id'] = $companyId;
        }

        $data['data']['created_by'] = $data['data']['created_by'] ?? $userId;
        $data['data']['updated_by'] = $data['data']['updated_by'] ?? $userId;
        return $data;
    }

    protected function applyTenantUpdateCallback(array $data): array
    {
        $request = service('request');
        if ($this->shouldEnforceTenant()) {
            $contextCompanyId = isset($request->company_id) ? (int) $request->company_id : 0;
            if ($contextCompanyId > 0) {
                $this->resolveContextCompanyId();
            }
        }
        $data['data']['updated_by'] = $request->user?->id ?? null;
        return $data;
    }

    protected function shouldEnforceTenant(): bool
    {
        return $this->requiresTenant
            && $this->enforceTenantGuard
            && ! $this->isCliRequest()
            && $this->isTenantEnforced()
            && ! $this->isSuperAdminContext();
    }

    protected function isTenantEnforced(): bool
    {
        return (bool) config(TenantConfig::class)->enforce;
    }

    protected function isSuperAdminContext(): bool
    {
        $request = service('request');
        $roles = is_array($request->roles ?? null) ? $request->roles : [];
        $superAdminRole = config(TenantConfig::class)->superAdminRole;
        return in_array($superAdminRole, $roles, true);
    }

    protected function resolveContextCompanyId(bool $throwOnMissing = true): ?int
    {
        if (! $this->requiresTenant || $this->isCliRequest() || $this->isSuperAdminContext() || ! $this->isTenantEnforced()) {
            return null;
        }

        $request = service('request');
        $companyId = isset($request->company_id) ? (int) $request->company_id : 0;
        if ($companyId > 0) {
            return $companyId;
        }

        if ($throwOnMissing) {
            throw new TenantAccessDeniedException('company_id baglami olmadan tenant sorgusu calistirilamaz');
        }

        return null;
    }

    protected function applyTenantScopeToBuilder(BaseBuilder $builder, ?string $table = null): void
    {
        if (! $this->shouldEnforceTenant()) {
            return;
        }

        $targetTable = $table !== null && $table !== '' ? $table : $this->table;
        if (str_contains($targetTable, '.') || str_contains($targetTable, ' ')) {
            return;
        }

        $companyId = $this->resolveContextCompanyId(false);
        if ($companyId === null) {
            return;
        }
        $builder->where($targetTable . '.company_id', $companyId);
    }

    protected function guardCompanyId(int $companyId): int
    {
        if (! $this->shouldEnforceTenant()) {
            return $companyId;
        }

        // Callers pass explicit company scope (RBAC, jobs). Require a match only when HTTP/JWT tenant is present.
        $contextCompanyId = $this->resolveContextCompanyId(false);
        if ($contextCompanyId !== null && $companyId !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        return $contextCompanyId ?? $companyId;
    }

    private function isCliRequest(): bool
    {
        return service('request') instanceof CLIRequest;
    }
}
