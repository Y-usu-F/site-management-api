<?php

namespace Tests\Unit\Services\Auth;

use App\Exceptions\PermissionNotFoundException;
use App\Models\PermissionModel;
use App\Services\Auth\PermissionCacheService;
use App\Services\Auth\PermissionService;
use App\Services\Auth\RoleService;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthConfig;
use Config\PermissionCatalog;

final class PermissionServiceTest extends CIUnitTestCase
{
    public function testTekRolCokluPermissionDoner(): void
    {
        $service = $this->makeService(
            roles: [
                ['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1],
            ],
            permissions: [
                ['code' => 'auth.session.list', 'scope' => 'company', 'is_active' => 1],
                ['code' => 'profile.view', 'scope' => 'company', 'is_active' => 1],
            ]
        );

        $codes = $service->getPermissionCodesForUser(10, 2);
        $this->assertSame(['auth.session.list', 'profile.view'], $codes);
    }

    public function testCokluRolCakisanPermissionlardaUnionTekilOlur(): void
    {
        $service = $this->makeService(
            roles: [
                ['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1],
                ['id' => 2, 'role_id' => 4, 'code' => 'reporter', 'name' => 'Reporter', 'role_version' => 1],
            ],
            permissions: [
                ['code' => 'auth.session.list', 'scope' => 'company', 'is_active' => 1],
                ['code' => 'auth.session.list', 'scope' => 'company', 'is_active' => 1],
                ['code' => 'profile.view', 'scope' => 'company', 'is_active' => 1],
            ]
        );

        $codes = $service->getPermissionCodesForUser(10, 2);
        $this->assertSame(['auth.session.list', 'profile.view'], $codes);
    }

    public function testPasifUserRoleDislandigindaBosListeDoner(): void
    {
        $service = $this->makeService(roles: [], permissions: []);
        $this->assertSame([], $service->getPermissionCodesForUser(10, 2));
    }

    public function testPasifRolePermissionDislandigindaBosListeDoner(): void
    {
        $service = $this->makeService(
            roles: [['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1]],
            permissions: []
        );
        $this->assertSame([], $service->getPermissionCodesForUser(10, 2));
    }

    public function testPasifPermissionKatalogdaAktifDegilseDislanir(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('exists')->with('auth.session.list')->willReturn(true);
        $catalog->method('get')->with('auth.session.list')->willReturn([
            'code' => 'auth.session.list',
            'label' => 'Auth Session List',
            'scope' => 'company',
            'description' => 'x',
            'is_active' => false,
        ]);

        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getActiveRolesForUser')->willReturn([
            ['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1],
        ]);
        $roleService->method('getRoleVersionForUser')->willReturn(1);

        $permissionModel = $this->createMock(PermissionModel::class);
        $permissionModel->method('getActivePermissionsForRoleIds')->with([3])->willReturn([
            ['code' => 'auth.session.list', 'scope' => 'company', 'is_active' => 1],
        ]);

        $cacheService = $this->createMock(PermissionCacheService::class);
        $cacheService->method('rememberPermissions')->willReturnCallback(
            static fn (int $userId, int $companyId, int $roleVersion, callable $resolver): array => $resolver()
        );

        $service = new PermissionService($roleService, $permissionModel, $catalog, $cacheService);
        $this->assertSame([], $service->getPermissionCodesForUser(10, 2));
    }

    public function testDeprecatedPermissionDislandigindaBosListeDoner(): void
    {
        $service = $this->makeService(
            roles: [['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1]],
            permissions: []
        );
        $this->assertSame([], $service->getPermissionCodesForUser(10, 2));
    }

    public function testCompanyMismatchDislandigindaBosListeDoner(): void
    {
        $roleService = $this->createMock(RoleService::class);
        $roleService->expects($this->once())
            ->method('getActiveRolesForUser')
            ->with(10, 2)
            ->willReturn([]);
        $roleService->expects($this->once())
            ->method('getRoleVersionForUser')
            ->with(10, 2)
            ->willReturn(1);

        $permissionModel = $this->createMock(PermissionModel::class);
        $permissionModel->expects($this->once())
            ->method('getActivePermissionsForRoleIds')
            ->with([])
            ->willReturn([]);

        $catalog = $this->createMock(PermissionCatalog::class);
        $cacheService = $this->createMock(PermissionCacheService::class);
        $cacheService->method('rememberPermissions')->willReturnCallback(
            static fn (int $userId, int $companyId, int $roleVersion, callable $resolver): array => $resolver()
        );
        $service = new PermissionService($roleService, $permissionModel, $catalog, $cacheService);
        $this->assertSame([], $service->getPermissionCodesForUser(10, 2));
    }

    public function testBilinmeyenDbPermissionCodeFailFastVerir(): void
    {
        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getActiveRolesForUser')->willReturn([
            ['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1],
        ]);
        $roleService->method('getRoleVersionForUser')->willReturn(1);

        $permissionModel = $this->createMock(PermissionModel::class);
        $permissionModel->method('getActivePermissionsForRoleIds')->willReturn([
            ['code' => 'unknown.code', 'scope' => 'company', 'is_active' => 1],
        ]);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('exists')->with('unknown.code')->willReturn(false);

        $cacheService = $this->createMock(PermissionCacheService::class);
        $cacheService->method('rememberPermissions')->willReturnCallback(
            static fn (int $userId, int $companyId, int $roleVersion, callable $resolver): array => $resolver()
        );

        $service = new PermissionService($roleService, $permissionModel, $catalog, $cacheService);

        $this->expectException(PermissionNotFoundException::class);
        $service->getPermissionCodesForUser(10, 2);
    }

    public function testUserHasPermissionTrueFalseDoner(): void
    {
        $service = $this->makeService(
            roles: [['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1]],
            permissions: [
                ['code' => 'auth.session.list', 'scope' => 'company', 'is_active' => 1],
            ]
        );

        $this->assertTrue($service->userHasPermission(10, 2, 'auth.session.list'));
        $this->assertFalse($service->userHasPermission(10, 2, 'auth.session.revoke'));
    }

    public function testCacheOnOffIcinPermissionSonucuTutarlidir(): void
    {
        $roles = [['id' => 1, 'role_id' => 3, 'code' => 'employee', 'name' => 'Employee', 'role_version' => 5]];
        $permissions = [['code' => 'auth.session.list', 'scope' => 'company', 'is_active' => 1]];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('get')->with('perm_10_2_v5')->willReturn(null);
        $cache->expects($this->once())->method('save')->with('perm_10_2_v5', $permissions, 300)->willReturn(true);

        $serviceWithCache = $this->makeServiceWithCacheConfig($roles, $permissions, true, $cache, 5);
        $serviceWithoutCache = $this->makeServiceWithCacheConfig($roles, $permissions, false, $cache, 5);

        $this->assertSame(
            $serviceWithCache->getPermissionCodesForUser(10, 2),
            $serviceWithoutCache->getPermissionCodesForUser(10, 2)
        );
    }

    /**
     * @param list<array{id:int,role_id:int,code:string,name:string,role_version:int}> $roles
     * @param list<array{code:string,scope:string,is_active:int}> $permissions
     */
    private function makeService(array $roles, array $permissions): PermissionService
    {
        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getActiveRolesForUser')->willReturn($roles);
        $roleService->method('getRoleVersionForUser')->willReturn(1);

        $roleIds = array_values(array_unique(array_map(static fn (array $role): int => $role['role_id'], $roles)));

        $permissionModel = $this->createMock(PermissionModel::class);
        $permissionModel->method('getActivePermissionsForRoleIds')->with($roleIds)->willReturn($permissions);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('exists')->willReturnCallback(static fn (string $code): bool => in_array($code, [
            'auth.session.list',
            'auth.session.revoke',
            'profile.view',
            'profile.update',
        ], true));
        $catalog->method('get')->willReturnCallback(static fn (string $code): array => [
            'code' => $code,
            'label' => $code,
            'scope' => 'company',
            'description' => 'x',
            'is_active' => true,
        ]);

        $cacheService = $this->createMock(PermissionCacheService::class);
        $cacheService->method('rememberPermissions')->willReturnCallback(
            static fn (int $userId, int $companyId, int $roleVersion, callable $resolver): array => $resolver()
        );

        return new PermissionService($roleService, $permissionModel, $catalog, $cacheService);
    }

    /**
     * @param list<array{id:int,role_id:int,code:string,name:string,role_version:int}> $roles
     * @param list<array{code:string,scope:string,is_active:int}> $permissions
     */
    private function makeServiceWithCacheConfig(
        array $roles,
        array $permissions,
        bool $cacheEnabled,
        CacheInterface $cache,
        int $roleVersion
    ): PermissionService {
        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getActiveRolesForUser')->willReturn($roles);
        $roleService->method('getRoleVersionForUser')->willReturn($roleVersion);

        $roleIds = array_values(array_unique(array_map(static fn (array $role): int => $role['role_id'], $roles)));

        $permissionModel = $this->createMock(PermissionModel::class);
        $permissionModel->method('getActivePermissionsForRoleIds')->with($roleIds)->willReturn($permissions);

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('exists')->willReturn(true);
        $catalog->method('get')->willReturnCallback(static fn (string $code): array => [
            'code' => $code,
            'label' => $code,
            'scope' => 'company',
            'description' => 'x',
            'is_active' => true,
        ]);

        $config = new AuthConfig();
        $config->permissionCacheEnabled = $cacheEnabled;
        $config->permissionCacheTtl = 300;
        $cacheService = new PermissionCacheService($cache, $config);

        return new PermissionService($roleService, $permissionModel, $catalog, $cacheService);
    }
}

