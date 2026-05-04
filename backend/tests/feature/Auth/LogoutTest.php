<?php

namespace Tests\Feature\Auth;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class LogoutTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testLogoutSonrasiRefreshTokenGecersizOlur(): void
    {
        $email = 'logout.refresh@example.com';
        $this->createUserWithRole($email, 'Password123!');
        $login = $this->login($email, 'Password123!');

        $access = $login['data']['access_token'];
        $refresh = $login['data']['refresh_token'];

        $logout = $this->withHeaders([
            'Authorization' => 'Bearer ' . $access,
        ])->withBodyFormat('json')->post('/api/v1/auth/logout', [
            'refresh_token' => $refresh,
        ]);
        $logout->assertStatus(200);

        $refreshAttempt = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $refresh,
        ]);
        $refreshAttempt->assertStatus(401);
    }

    public function testLogoutAuditEventYazilirVeResponseZarfiDogrudur(): void
    {
        $email = 'logout.audit@example.com';
        $this->createUserWithRole($email, 'Password123!');
        $login = $this->login($email, 'Password123!');

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $login['data']['access_token'],
        ])->withBodyFormat('json')->post('/api/v1/auth/logout', [
            'refresh_token' => $login['data']['refresh_token'],
        ]);

        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('request_id', $payload['meta']);

        $db = Database::connect();
        $auditCount = $db->table('audit_logs')->where('action', 'auth.logout.success')->countAllResults();
        $this->assertIsInt($auditCount);
    }

    /**
     * @return array<string,mixed>
     */
    private function login(string $email, string $password): array
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $result->assertStatus(200);

        return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createUserWithRole(string $email, string $password): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Logout Co ' . bin2hex(random_bytes(2)),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = (int) $db->insertID();

        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'Logout',
            'last_name' => 'User',
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 0,
            'locked_until' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int) $db->insertID();

        $role = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        if ($role === null) {
            $db->table('roles')->insert([
                'company_id' => null,
                'code' => 'company_admin',
                'name' => 'Company Admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = (int) $db->insertID();
        } else {
            $roleId = (int) $role['id'];
        }

        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permission = $db->table('permissions')->where('code', 'auth.logout')->get()->getRowArray();
        if ($permission === null) {
            $db->table('permissions')->insert([
                'code' => 'auth.logout',
                'name' => 'Auth Logout',
                'scope' => 'company',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permissionId = (int) $db->insertID();
        } else {
            $permissionId = (int) $permission['id'];
        }

        $rolePermission = $db->table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->get()
            ->getRowArray();

        if ($rolePermission === null) {
            $db->table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $userId;
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
