<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Libraries\Auth\JwtManager;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuthMePermissionsFeatureTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testAuthMeReturnsNestedUserCompanyIdAndPermissionCodes(): void
    {
        [$userId, $companyId] = $this->createActiveUser('auth.me.payload@example.com');
        $roleId = $this->createRole('employee_payload');
        $this->assignRole($userId, $companyId, $roleId, true);
        $this->attachPermissionToRole($roleId, 'auth.me.view', true);
        $this->attachPermissionToRole($roleId, 'site.list', true);

        $token = $this->issueToken($userId, $companyId, ['employee_payload']);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'auth-me-permissions-payload',
        ])->get('/api/v1/auth/me');

        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success'] ?? false);
        $data = $payload['data'];
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('company_id', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertSame($companyId, (int) $data['company_id']);
        $this->assertSame($userId, (int) $data['user']['id']);
        $perms = $data['permissions'];
        $this->assertIsArray($perms);
        $this->assertContains('auth.me.view', $perms);
        $this->assertContains('site.list', $perms);
        $sorted = $perms;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $perms, 'permission codes should be sorted deterministically');
    }

    public function testInactivePermissionRowExcludedFromAuthMePermissions(): void
    {
        [$userId, $companyId] = $this->createActiveUser('auth.me.inactive.perm@example.com');
        $roleId = $this->createRole('employee_inactive_perm');
        $this->assignRole($userId, $companyId, $roleId, true);
        $this->attachPermissionToRole($roleId, 'auth.me.view', true);
        // Attach catalog-backed permission but mark permission row inactive; linkage stays active.
        $this->attachPermissionToRole($roleId, 'deposit.cancel', false);

        $token = $this->issueToken($userId, $companyId, ['employee_inactive_perm']);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'auth-me-inactive-perm',
        ])->get('/api/v1/auth/me');

        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $perms = $payload['data']['permissions'];
        $this->assertNotContains('deposit.cancel', $perms);

        $db = Database::connect();
        $db->table('permissions')->where('code', 'deposit.cancel')->update([
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        cache()->clean();
    }

    public function testInactiveUserRoleAssignmentDoesNotExposeItsPermissions(): void
    {
        [$userId, $companyId] = $this->createActiveUser('auth.me.inactive.role@example.com');
        $inactiveRoleId = $this->createRole('ghost_role_me');
        $activeRoleId = $this->createRole('alive_role_me');
        $this->assignRole($userId, $companyId, $inactiveRoleId, false);
        $this->attachPermissionToRole($inactiveRoleId, 'site.list', true);
        $this->assignRole($userId, $companyId, $activeRoleId, true);
        $this->attachPermissionToRole($activeRoleId, 'auth.me.view', true);

        $token = $this->issueToken($userId, $companyId, ['ghost_role_me', 'alive_role_me']);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'auth-me-inactive-role',
        ])->get('/api/v1/auth/me');

        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $perms = $payload['data']['permissions'];
        $this->assertContains('auth.me.view', $perms);
        $this->assertNotContains('site.list', $perms);
    }

    private function createActiveUser(string $email): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Auth Me Company',
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
            'first_name' => 'Auth',
            'last_name' => 'Me',
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

    private function assignRole(int $userId, int $companyId, int $roleId, bool $active): void
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'is_active' => $active ? 1 : 0,
            'role_version' => 1,
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
            $db->table('permissions')->where('id', $permissionId)->update([
                'is_active' => $permissionActive ? 1 : 0,
                'deprecated_at' => null,
                'updated_at' => $now,
            ]);
        }

        $existing = $db->table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->get()
            ->getRowArray();
        if ($existing === null) {
            $db->table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $db->table('role_permissions')->where('id', $existing['id'])->update([
                'is_active' => 1,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }
    }

    private function issueToken(int $userId, int $companyId, array $roles): string
    {
        return (new JwtManager())->issue([
            'sub' => $userId,
            'company_id' => $companyId,
            'roles' => $roles,
        ], 600);
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
