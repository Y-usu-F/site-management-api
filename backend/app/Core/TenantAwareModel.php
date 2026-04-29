<?php

namespace App\Core;

use App\Exceptions\TenantAccessDeniedException;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;
use Config\TenantConfig;

abstract class TenantAwareModel extends Model
{
    protected bool $requiresTenant = true;
    protected bool $enforceTenantGuard = true;
    protected ?int $resolvedCompanyId = null;

    protected function initialize(): void
    {
        parent::initialize();
        $this->beforeFind = array_values(array_unique(array_merge($this->beforeFind, ['applyTenantScopeCallback'])));
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

    protected function beforeInsert(array $data)
    {
        $request = service('request');
        $companyId = $this->resolveContextCompanyId(false);
        $userId = $request->user?->id ?? null;

        if ($this->requiresTenant) {
            if ($companyId === null) {
                throw new TenantAccessDeniedException('Tenant baglami bulunamadi');
            }
            $data['data']['company_id'] = $companyId;
        }

        $data['data']['created_by'] = $data['data']['created_by'] ?? $userId;
        $data['data']['updated_by'] = $data['data']['updated_by'] ?? $userId;
        return $data;
    }

    protected function beforeUpdate(array $data)
    {
        $request = service('request');
        $this->resolveContextCompanyId();
        $data['data']['updated_by'] = $request->user?->id ?? null;
        return $data;
    }

    protected function shouldEnforceTenant(): bool
    {
        return $this->requiresTenant
            && $this->enforceTenantGuard
            && ! is_cli()
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
        if ($this->resolvedCompanyId !== null) {
            return $this->resolvedCompanyId;
        }

        if (! $this->requiresTenant || is_cli() || $this->isSuperAdminContext() || ! $this->isTenantEnforced()) {
            return null;
        }

        $request = service('request');
        $companyId = isset($request->company_id) ? (int) $request->company_id : 0;
        if ($companyId > 0) {
            $this->resolvedCompanyId = $companyId;
            return $this->resolvedCompanyId;
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

        $companyId = $this->resolveContextCompanyId();
        $builder->where($targetTable . '.company_id', $companyId);
    }

    protected function guardCompanyId(int $companyId): int
    {
        if (! $this->shouldEnforceTenant()) {
            return $companyId;
        }

        $contextCompanyId = $this->resolveContextCompanyId();
        if ($contextCompanyId !== null && $companyId !== $contextCompanyId) {
            throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');
        }

        return $contextCompanyId ?? $companyId;
    }
}
