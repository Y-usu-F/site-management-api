<?php

namespace Tests\Feature\Auth;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class RefreshTokenTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testRefreshTokenIleYeniAccessTokenAlinir(): void
    {
        $email = 'refresh.success@example.com';
        $this->createUserWithRole($email, 'Password123!');

        $loginData = $this->login($email, 'Password123!');
        $refreshToken = $loginData['data']['refresh_token'];

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success']);
        $this->assertNotSame($refreshToken, $payload['data']['refresh_token']);
        $this->assertIsString($payload['data']['access_token'] ?? null);
    }

    public function testRefreshRotationEskiTokeniRevokeEderVeTekrarKullanilamaz(): void
    {
        $email = 'refresh.rotate@example.com';
        $userId = $this->createUserWithRole($email, 'Password123!');

        $loginData = $this->login($email, 'Password123!');
        $oldRefresh = $loginData['data']['refresh_token'];

        $rotateResult = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $oldRefresh,
        ]);
        $rotateResult->assertStatus(200);

        $reuseResult = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $oldRefresh,
        ]);
        $reuseResult->assertStatus(401);
        $reusePayload = json_decode($reuseResult->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('TOKEN_REUSED', $reusePayload['errors']['error_code'] ?? null);

        $db = Database::connect();
        $rows = $db->table('user_refresh_tokens')->where('user_id', $userId)->get()->getResultArray();
        $this->assertGreaterThanOrEqual(2, count($rows));
        foreach ($rows as $row) {
            $this->assertNotNull($row['revoked_at']);
        }
    }

    public function testRefreshSuccessFailReuseAuditEventleriYazilir(): void
    {
        $email = 'refresh.audit@example.com';
        $this->createUserWithRole($email, 'Password123!');

        $loginData = $this->login($email, 'Password123!');
        $refreshToken = $loginData['data']['refresh_token'];

        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertStatus(200);

        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ])->assertStatus(401);

        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertStatus(401);

        $db = Database::connect();
        $this->assertGreaterThan(0, $db->table('audit_logs')->where('event', 'auth.refresh.success')->countAllResults());
        $this->assertGreaterThan(0, $db->table('audit_logs')->where('event', 'auth.refresh.failed')->countAllResults());
        $this->assertGreaterThan(0, $db->table('audit_logs')->where('event', 'auth.refresh.reuse_detected')->countAllResults());
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
            'name' => 'Refresh Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Refresh',
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
