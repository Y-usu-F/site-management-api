<?php

namespace Tests\Feature\Rbac;

use App\Libraries\Auth\JwtManager;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class RbacAuthorizationTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testSuperAdminTumPermissionlaraErisir(): void
    {
        [$userId, $companyId] = $this->createActiveUser('rbac.super@example.com');
        $roleId = $this->createRole('super_admin');
        $this->assignRole($userId, $companyId, $roleId, true);

        $token = $this->issueToken($userId, $companyId, ['super_admin']);

        $sessions = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'rbac-super-1',
        ])->get('/api/v1/auth/sessions');
        $sessions->assertStatus(200);

        $me = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'rbac-super-2',
        ])->get('/api/v1/auth/me');
        $me->assertStatus(200);
    }

    public function testEmployeeSadeceAtanmisPermissioniKullanir(): void
    {
        [$userId, $companyId] = $this->createActiveUser('rbac.employee@example.com');
        $employeeRoleId = $this->createRole('employee');
        $this->assignRole($userId, $companyId, $employeeRoleId, true);
        $this->attachPermissionToRole($employeeRoleId, 'auth.session.list', true);

        $token = $this->issueToken($userId, $companyId, ['employee']);

        $allow = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'rbac-employee-allow',
        ])->get('/api/v1/auth/sessions');
        $allow->assertStatus(200);

        $deny = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'rbac-employee-deny',
        ])->delete('/api/v1/auth/sessions/999999');
        $deny->assertStatus(403);

        $payload = json_decode($deny->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('FORBIDDEN', $payload['errors']['error_code'] ?? null);
        $this->assertArrayHasKey('request_id', $payload['meta'] ?? []);
    }

    public function testTokenYoksa401DonerVeMetaRequestIdVardir(): void
    {
        $result = $this->withHeaders([
            'X-Request-Id' => 'rbac-token-missing',
        ])->get('/api/v1/auth/sessions');
        $result->assertStatus(401);

        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('request_id', $payload['meta'] ?? []);
    }

    private function createActiveUser(string $email): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'RBAC Company ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'RBAC',
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

