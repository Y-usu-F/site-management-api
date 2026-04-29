<?php

namespace Tests\Unit\Services\Auth;

use App\Models\UserRoleModel;
use App\Services\Auth\PermissionCacheService;
use App\Services\Auth\RoleAssignmentService;
use App\Services\Common\AuditLogService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthConfig;
use RuntimeException;

final class RoleAssignmentInvalidationTest extends CIUnitTestCase
{
    public function testRolEkleRoleVersionArtirir(): void
    {
        $userRoleModel = $this->createMock(UserRoleModel::class);
        $userRoleModel->expects($this->once())
            ->method('assignRoleToUser')
            ->with(10, 2, 4)
            ->willReturn(3);

        $cache = $this->createMock(PermissionCacheService::class);
        $cache->expects($this->once())->method('invalidateUserCompany')->with(10, 2);

        $audit = $this->createMock(AuditLogService::class);
        $audit->expects($this->once())
            ->method('recordEvent')
            ->with(
                'rbac.role.assigned',
                $this->callback(static function (array $payload): bool {
                    return ($payload['actor_user_id'] ?? null) === 10
                        && ($payload['target_user_id'] ?? null) === 10
                        && ($payload['company_id'] ?? null) === 2
                        && ($payload['role_id'] ?? null) === 4
                        && ($payload['status'] ?? null) === 'success'
                        && isset($payload['meta'])
                        && is_array($payload['meta']);
                })
            )
            ->willReturn(true);

        $service = new RoleAssignmentService($userRoleModel, $cache, $audit);
        $newVersion = $service->assignRole(10, 2, 4);

        $this->assertSame(3, $newVersion);
    }

    public function testRolKaldirRoleVersionArtirir(): void
    {
        $userRoleModel = $this->createMock(UserRoleModel::class);
        $userRoleModel->expects($this->once())
            ->method('revokeRoleFromUser')
            ->with(10, 2, 4)
            ->willReturn(4);

        $cache = $this->createMock(PermissionCacheService::class);
        $cache->expects($this->once())->method('invalidateUserCompany')->with(10, 2);

        $audit = $this->createMock(AuditLogService::class);
        $audit->expects($this->once())
            ->method('recordEvent')
            ->with(
                'rbac.role.revoked',
                $this->callback(static function (array $payload): bool {
                    return ($payload['actor_user_id'] ?? null) === 10
                        && ($payload['target_user_id'] ?? null) === 10
                        && ($payload['company_id'] ?? null) === 2
                        && ($payload['role_id'] ?? null) === 4
                        && ($payload['status'] ?? null) === 'success'
                        && isset($payload['meta'])
                        && is_array($payload['meta']);
                })
            )
            ->willReturn(true);

        $service = new RoleAssignmentService($userRoleModel, $cache, $audit);
        $newVersion = $service->revokeRole(10, 2, 4);

        $this->assertSame(4, $newVersion);
    }

    public function testRoleVersionDegisinceBuildKeyDegisir(): void
    {
        $service = new PermissionCacheService(
            cacheHandler: new class {
                public function get(string $key): mixed
                {
                    return null;
                }
                public function save(string $key, mixed $value, int $ttl = 60): bool
                {
                    return true;
                }
            },
            authConfig: $this->enabledConfig()
        );

        $this->assertNotSame(
            $service->buildKey(10, 2, 1),
            $service->buildKey(10, 2, 2)
        );
    }

    public function testEskiCacheVarkenYeniRoleVersionYeniSonucuDondurur(): void
    {
        $cache = new class {
            /** @var array<string, mixed> */
            public array $store = [];
            public function get(string $key): mixed
            {
                return $this->store[$key] ?? null;
            }
            public function save(string $key, mixed $value, int $ttl = 60): bool
            {
                $this->store[$key] = $value;
                return true;
            }
            public function deleteMatching(string $pattern): bool
            {
                return true;
            }
        };

        $service = new PermissionCacheService($cache, $this->enabledConfig());

        $v1 = $service->rememberPermissions(10, 2, 1, static fn (): array => ['auth.session.list']);
        $v2 = $service->rememberPermissions(10, 2, 2, static fn (): array => ['auth.session.revoke']);

        $this->assertSame(['auth.session.list'], $v1);
        $this->assertSame(['auth.session.revoke'], $v2);
    }

    public function testPermissionPasiflestirmeSonrasiIlgiliKullanicilarInvalidateEdilir(): void
    {
        $userRoleModel = $this->createMock(UserRoleModel::class);
        $userRoleModel->expects($this->once())
            ->method('getActiveUserCompanyPairsByRoleIds')
            ->with([3])
            ->willReturn([
                ['user_id' => 10, 'company_id' => 2],
                ['user_id' => 11, 'company_id' => 2],
            ]);
        $userRoleModel->expects($this->exactly(2))
            ->method('bumpRoleVersionForUserCompany')
            ->willReturn(9);

        $cache = $this->createMock(PermissionCacheService::class);
        $cache->expects($this->exactly(2))->method('invalidateUserCompany');

        $service = new RoleAssignmentService($userRoleModel, $cache);
        $service->invalidateUsersByRoleIds([3]);

        $this->assertTrue(true);
    }

    public function testCacheDisabledModdaSonucDogruKalir(): void
    {
        $service = new PermissionCacheService(
            cacheHandler: new class {
                public function get(string $key): mixed
                {
                    return ['cached'];
                }
                public function save(string $key, mixed $value, int $ttl = 60): bool
                {
                    return true;
                }
            },
            authConfig: $this->disabledConfig()
        );

        $callCount = 0;
        $resolver = static function () use (&$callCount): array {
            $callCount++;
            return ['fresh'];
        };

        $this->assertSame(['fresh'], $service->rememberPermissions(10, 2, 1, $resolver));
        $this->assertSame(['fresh'], $service->rememberPermissions(10, 2, 1, $resolver));
        $this->assertSame(2, $callCount);
    }

    public function testCacheHatasiInvalidationAkisiniBozmaz(): void
    {
        $userRoleModel = $this->createMock(UserRoleModel::class);
        $userRoleModel->method('assignRoleToUser')->willReturn(5);

        $cache = $this->createMock(PermissionCacheService::class);
        $cache->method('invalidateUserCompany')->willThrowException(new RuntimeException('cache fail'));

        $service = new RoleAssignmentService($userRoleModel, $cache);
        $newVersion = $service->assignRole(10, 2, 4);

        $this->assertSame(5, $newVersion);
    }

    public function testAuditFailureOlsaBileAssignTamamlanir(): void
    {
        $userRoleModel = $this->createMock(UserRoleModel::class);
        $userRoleModel->method('assignRoleToUser')->willReturn(6);

        $cache = $this->createMock(PermissionCacheService::class);
        $cache->expects($this->once())->method('invalidateUserCompany')->with(10, 2);

        $audit = $this->createMock(AuditLogService::class);
        $audit->method('recordEvent')->willThrowException(new RuntimeException('audit fail'));

        $service = new RoleAssignmentService($userRoleModel, $cache, $audit);
        $newVersion = $service->assignRole(10, 2, 4, 99);

        $this->assertSame(6, $newVersion);
    }

    public function testAuditFailureOlsaBileRevokeTamamlanir(): void
    {
        $userRoleModel = $this->createMock(UserRoleModel::class);
        $userRoleModel->method('revokeRoleFromUser')->willReturn(7);

        $cache = $this->createMock(PermissionCacheService::class);
        $cache->expects($this->once())->method('invalidateUserCompany')->with(10, 2);

        $audit = $this->createMock(AuditLogService::class);
        $audit->method('recordEvent')->willThrowException(new RuntimeException('audit fail'));

        $service = new RoleAssignmentService($userRoleModel, $cache, $audit);
        $newVersion = $service->revokeRole(10, 2, 4, 99);

        $this->assertSame(7, $newVersion);
    }

    private function enabledConfig(): AuthConfig
    {
        $config = new AuthConfig();
        $config->permissionCacheEnabled = true;
        $config->permissionCacheTtl = 300;
        return $config;
    }

    private function disabledConfig(): AuthConfig
    {
        $config = new AuthConfig();
        $config->permissionCacheEnabled = false;
        $config->permissionCacheTtl = 300;
        return $config;
    }
}

