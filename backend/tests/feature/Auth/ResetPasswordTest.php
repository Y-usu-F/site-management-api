<?php

namespace Tests\Feature\Auth;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class ResetPasswordTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $seed = '';
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testGecerliResetTokenIlePasswordDegisirVeUsedAtSetEdilir(): void
    {
        [$userId, $email] = $this->createUserWithRole('reset.valid@example.com', 'Password123!');
        $token = $this->insertResetToken($userId, 3600);

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => $token,
            'password' => 'NewPass123!',
        ]);
        $result->assertStatus(200);

        $db = Database::connect();
        $user = $db->table('users')->where('email', $email)->get()->getRowArray();
        $this->assertTrue(password_verify('NewPass123!', (string) ($user['password_hash'] ?? '')));

        $row = $db->table('password_reset_tokens')->where('token_hash', hash('sha256', $token))->get()->getRowArray();
        $this->assertNotNull($row['used_at'] ?? null);
    }

    public function testSuresiDolmusResetTokenReddedilir(): void
    {
        [$userId] = $this->createUserWithRole('reset.expired@example.com', 'Password123!');
        $token = $this->insertResetToken($userId, -60);

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => $token,
            'password' => 'NewPass123!',
        ]);
        $result->assertStatus(401);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('TOKEN_EXPIRED', $payload['errors']['error_code'] ?? null);
    }

    public function testResetTokenIkinciKezKullanilamaz(): void
    {
        [$userId] = $this->createUserWithRole('reset.singleuse@example.com', 'Password123!');
        $token = $this->insertResetToken($userId, 3600);

        $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => $token,
            'password' => 'NewPass123!',
        ])->assertStatus(200);

        $second = $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => $token,
            'password' => 'AnotherPass123!',
        ]);
        $second->assertStatus(401);
        $payload = json_decode($second->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('TOKEN_ALREADY_USED', $payload['errors']['error_code'] ?? null);
    }

    public function testResetPasswordAuditVeSessionRevokeCalisir(): void
    {
        [$userId, $email] = $this->createUserWithRole('reset.audit@example.com', 'Password123!');
        $loginOne = $this->login($email, 'Password123!');
        $loginTwo = $this->login($email, 'Password123!');
        $this->assertNotEmpty($loginOne['data']['refresh_token'] ?? null);
        $this->assertNotEmpty($loginTwo['data']['refresh_token'] ?? null);

        $token = $this->insertResetToken($userId, 3600);
        $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => $token,
            'password' => 'NewPass123!',
        ])->assertStatus(200);

        $db = Database::connect();
        $successAudit = $db->table('audit_logs')->where('event', 'auth.reset_password.success')->countAllResults();
        $this->assertGreaterThan(0, $successAudit);

        $revokedSessions = $db->table('user_refresh_tokens')
            ->where('user_id', $userId)
            ->where('revoked_reason', 'password_reset')
            ->countAllResults();
        $this->assertGreaterThan(0, $revokedSessions);

        $fail = $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => 'invalid-token-value',
            'password' => 'AnotherPass123!',
        ]);
        $fail->assertStatus(401);
        $failAudit = $db->table('audit_logs')->where('event', 'auth.reset_password.failed')->countAllResults();
        $this->assertGreaterThan(0, $failAudit);
    }

    /**
     * @return array{0:int,1:string}
     */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Reset Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Reset',
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

        return [$userId, $email];
    }

    private function insertResetToken(int $userId, int $ttlSeconds): string
    {
        $plain = bin2hex(random_bytes(32));
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('password_reset_tokens')->insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlSeconds),
            'used_at' => null,
            'requested_ip' => '127.0.0.1',
            'requested_user_agent' => 'phpunit',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $plain;
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

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}
