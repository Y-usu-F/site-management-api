<?php

namespace Tests\Feature\Rbac;

use App\Services\Auth\PermissionCacheService;
use App\Services\Auth\PermissionMatrixService;
use App\Services\Auth\PermissionService;
use App\Services\Auth\RoleAssignmentService;
use CodeIgniter\Cache\CacheInterface;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\AuthConfig;
use Config\Database;

final class RbacCacheInvalidationTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testRoleDegisikligiSonrasiCacheInvalidationCalisir(): void
    {
        [$userId, $companyId] = $this->createActiveUser('cache.invalidate@example.com');
        $roleId = $this->createRole('employee');
        $this->attachPermissionToRole($roleId, 'auth.session.list', true);
        $this->assignRole($userId, $companyId, $roleId, true, 1);

        $permissionService = new PermissionService();
        $first = $permissionService->getPermissionCodesForUser($userId, $companyId);
        $this->assertContains('auth.session.list', $first);

        $assignmentService = new RoleAssignmentService();
        $assignmentService->revokeRole($userId, $companyId, $roleId);

        $afterRevoke = $permissionService->getPermissionCodesForUser($userId, $companyId);
        $this->assertNotContains('auth.session.list', $afterRevoke);
    }

    public function testCacheAcikKapaliAuthorizationSonucuTutarlidir(): void
    {
        [$userId, $companyId] = $this->createActiveUser('cache.toggle@example.com');
        $roleId = $this->createRole('employee');
        $this->attachPermissionToRole($roleId, 'auth.session.list', true);
        $this->assignRole($userId, $companyId, $roleId, true, 5);

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->method('save')->willReturn(true);

        $enabled = new AuthConfig();
        $enabled->permissionCacheEnabled = true;
        $enabled->permissionCacheTtl = 300;

        $disabled = new AuthConfig();
        $disabled->permissionCacheEnabled = false;
        $disabled->permissionCacheTtl = 300;

        $cacheEnabledService = new PermissionService(
            permissionCacheService: new PermissionCacheService($cache, $enabled)
        );
        $cacheDisabledService = new PermissionService(
            permissionCacheService: new PermissionCacheService($cache, $disabled)
        );

        $this->assertSame(
            $cacheEnabledService->getPermissionCodesForUser($userId, $companyId),
            $cacheDisabledService->getPermissionCodesForUser($userId, $companyId)
        );
    }

    public function testSeedMatrisiVeRuntimeAuthorizationTutarlidirVeSmokePass(): void
    {
        [$userId, $companyId] = $this->createActiveUser('matrix.runtime@example.com');
        $roleId = $this->createRole('employee');
        $this->attachPermissionToRole($roleId, 'auth.session.list', true);
        $this->assignRole($userId, $companyId, $roleId, true, 2);

        $matrix = new PermissionMatrixService();
        $matrixResult = $matrix->validateAll();
        $this->assertTrue($matrixResult['valid'], implode('; ', $matrixResult['errors']));

        $health = $this->get('/health');
        $health->assertStatus(200);

        $ready = $this->get('/ready');
        $ready->assertStatus(200);

        $meNoToken = $this->get('/api/v1/auth/me');
        $meNoToken->assertStatus(401);
    }

    private function createActiveUser(string $email): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Cache Co ' . bin2hex(random_bytes(2)),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = (int) $db->insertID();

        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'first_name' => 'Cache',
            'last_name' => 'User',
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [(int) $db->insertID(), $companyId];
    }

    private function createRole(string $code): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('roles')->insert([
            'company_id' => null,
            'code' => $code,
            'name' => strtoupper($code),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $db->insertID();
    }

    private function assignRole(int $userId, int $companyId, int $roleId, bool $active, int $roleVersion): void
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'is_active' => $active ? 1 : 0,
            'role_version' => $roleVersion,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function attachPermissionToRole(int $roleId, string $permissionCode, bool $permissionActive): void
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $permission = $db->table('permissions')->where('code', $permissionCode)->get()->getRowArray();
        if ($permission === null) {
            $db->table('permissions')->insert([
                'code' => $permissionCode,
                'name' => $permissionCode,
                'scope' => 'company',
                'is_active' => $permissionActive ? 1 : 0,
                'deprecated_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permissionId = (int) $db->insertID();
        } else {
            $permissionId = (int) $permission['id'];
        }

        $db->table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}

