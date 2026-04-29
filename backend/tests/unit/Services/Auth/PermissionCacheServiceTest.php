<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\PermissionCacheService;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthConfig;
use RuntimeException;

final class PermissionCacheServiceTest extends CIUnitTestCase
{
    public function testBuildKeyDeterministic(): void
    {
        $service = new PermissionCacheService(
            cacheHandler: $this->createMock(CacheInterface::class),
            authConfig: $this->enabledConfig()
        );

        $keyA = $service->buildKey(10, 2, 7);
        $keyB = $service->buildKey(10, 2, 7);

        $this->assertSame($keyA, $keyB);
        $this->assertSame('perm_10_2_v7', $keyA);
        $this->assertStringNotContainsString(':', $keyA);
    }

    public function testFarkliCompanyFarkliKeyUretir(): void
    {
        $service = new PermissionCacheService(
            cacheHandler: $this->createMock(CacheInterface::class),
            authConfig: $this->enabledConfig()
        );

        $this->assertNotSame(
            $service->buildKey(10, 2, 1),
            $service->buildKey(10, 3, 1)
        );
    }

    public function testFarkliRoleVersionFarkliKeyUretir(): void
    {
        $service = new PermissionCacheService(
            cacheHandler: $this->createMock(CacheInterface::class),
            authConfig: $this->enabledConfig()
        );

        $this->assertNotSame(
            $service->buildKey(10, 2, 1),
            $service->buildKey(10, 2, 2)
        );
    }

    public function testCacheDisabledResolverHerCagridaCalisir(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('get');
        $cache->expects($this->never())->method('save');

        $service = new PermissionCacheService($cache, $this->disabledConfig());

        $calls = 0;
        $resolver = static function () use (&$calls): array {
            $calls++;
            return [['code' => 'auth.session.list']];
        };

        $service->rememberPermissions(10, 2, 1, $resolver);
        $service->rememberPermissions(10, 2, 1, $resolver);

        $this->assertSame(2, $calls);
    }

    public function testCacheEnabledMissHitDavranisi(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->exactly(2))
            ->method('get')
            ->with('perm_10_2_v5')
            ->willReturnOnConsecutiveCalls(null, [['code' => 'auth.session.list']]);
        $cache->expects($this->once())
            ->method('save')
            ->with('perm_10_2_v5', [['code' => 'auth.session.list']], 300)
            ->willReturn(true);

        $service = new PermissionCacheService($cache, $this->enabledConfig());

        $calls = 0;
        $resolver = static function () use (&$calls): array {
            $calls++;
            return [['code' => 'auth.session.list']];
        };

        $first = $service->rememberPermissions(10, 2, 5, $resolver);
        $second = $service->rememberPermissions(10, 2, 5, $resolver);

        $this->assertSame([['code' => 'auth.session.list']], $first);
        $this->assertSame([['code' => 'auth.session.list']], $second);
        $this->assertSame(1, $calls);
    }

    public function testCacheFailureFailOpenDavranisi(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->willThrowException(new RuntimeException('cache down'));
        $cache->expects($this->never())->method('save');

        $service = new PermissionCacheService($cache, $this->enabledConfig());

        $calls = 0;
        $result = $service->rememberPermissions(10, 2, 1, static function () use (&$calls): array {
            $calls++;
            return [['code' => 'profile.view']];
        });

        $this->assertSame(1, $calls);
        $this->assertSame([['code' => 'profile.view']], $result);
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

