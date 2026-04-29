<?php

namespace Tests\Unit\Services\Auth;

use App\Exceptions\AuthorizationException;
use App\Exceptions\PermissionNotFoundException;
use App\Services\Auth\AuthorizationService;
use App\Services\Auth\PermissionService;
use App\Services\Auth\RoleService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\PermissionCatalog;
use Config\TenantConfig;

final class AuthorizationServiceTest extends CIUnitTestCase
{
    public function testSuperAdminHerPermissiondaAllowOlur(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->never())->method('userHasPermission');

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['super_admin']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->expects($this->once())->method('assertExists')->with('permission.manage');
        $catalog->expects($this->once())->method('scopeOf')->with('permission.manage')->willReturn('system');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'permission.manage', 999);

        $this->assertTrue($decision['allowed']);
        $this->assertSame('super_admin_override', $decision['reason']);
        $this->assertTrue($decision['is_super_admin']);
    }

    public function testSystemScopeNormalUserIcinDenyOlur(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->with(10, 2, 'permission.manage')->willReturn(true);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['manager']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('system');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'permission.manage');

        $this->assertFalse($decision['allowed']);
        $this->assertSame('system_scope_requires_super_admin', $decision['reason']);
    }

    public function testCompanyScopeDogruTenantteAllowOlur(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->with(10, 2, 'profile.view')->willReturn(true);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['editor']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'profile.view', 2);

        $this->assertTrue($decision['allowed']);
        $this->assertNull($decision['reason']);
        $this->assertSame('company', $decision['scope']);
    }

    public function testCompanyScopeYanlisTenantteDenyOlur(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->with(10, 2, 'profile.view')->willReturn(true);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['editor']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'profile.view', 99);

        $this->assertFalse($decision['allowed']);
        $this->assertSame('tenant_mismatch', $decision['reason']);
    }

    public function testCompanyAdminTenantDisindaDenyOlur(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->with(10, 2, 'profile.view')->willReturn(true);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['company_admin']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'profile.view', 3);

        $this->assertFalse($decision['allowed']);
        $this->assertSame('tenant_mismatch', $decision['reason']);
    }

    public function testPermissionYoksaDenyOlur(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->with(10, 2, 'profile.view')->willReturn(false);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['editor']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'profile.view', 2);

        $this->assertFalse($decision['allowed']);
        $this->assertSame('permission_missing', $decision['reason']);
    }

    public function testEmployeeSadeceAtanmisPermissionlaraErisir(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->exactly(2))
            ->method('userHasPermission')
            ->willReturnCallback(
                static fn (int $userId, int $companyId, string $permissionCode): bool => $permissionCode === 'profile.view'
            );

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['employee']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());

        $allowed = $service->authorize(10, 2, 'profile.view', 2);
        $denied = $service->authorize(10, 2, 'auth.session.revoke', 2);

        $this->assertTrue($allowed['allowed']);
        $this->assertFalse($denied['allowed']);
        $this->assertSame('permission_missing', $denied['reason']);
    }

    public function testUnknownPermissionExceptionFirlatir(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $roleService = $this->createMock(RoleService::class);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists')->willThrowException(new PermissionNotFoundException('x'));

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());

        $this->expectException(PermissionNotFoundException::class);
        $service->authorize(10, 2, 'unknown.permission');
    }

    public function testEnsureAuthorizedDenyDurumundaExceptionFirlatir(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->willReturn(false);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['editor']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());

        $this->expectException(AuthorizationException::class);
        $service->ensureAuthorized(10, 2, 'profile.view', 2);
    }

    public function testReturnedStructureDogrulanir(): void
    {
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('userHasPermission')->willReturn(true);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleCodesForUser')->willReturn(['editor']);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists');
        $catalog->method('scopeOf')->willReturn('company');

        $service = new AuthorizationService($permissionService, $roleService, $catalog, new TenantConfig());
        $decision = $service->authorize(10, 2, 'profile.view', 2);

        $this->assertSame(
            ['allowed', 'reason', 'permission', 'scope', 'is_super_admin'],
            array_keys($decision)
        );
        $this->assertSame('profile.view', $decision['permission']);
        $this->assertSame('company', $decision['scope']);
        $this->assertFalse($decision['is_super_admin']);
    }
}

