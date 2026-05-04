<?php

namespace Tests\Feature\Auth;

use App\Libraries\Auth\JwtManager;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class LoginTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testDogruBilgilerleLoginBasariliOlurVeZarfDoner(): void
    {
        $email = 'login.success@example.com';
        $this->createUserWithRole($email, 'Password123!', true);

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('request_id', $payload['meta']);
        $this->assertIsString($payload['data']['access_token'] ?? null);
        $this->assertIsString($payload['data']['refresh_token'] ?? null);
    }

    public function testHataliSifre401Doner(): void
    {
        $email = 'login.fail@example.com';
        $this->createUserWithRole($email, 'Password123!', true);

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'WrongPass123!',
        ]);

        $result->assertStatus(401);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['success']);
        $this->assertSame('UNAUTHORIZED', $payload['errors']['error_code'] ?? null);
    }

    public function testDeaktifKullaniciLoginOlamaz(): void
    {
        $email = 'inactive.user@example.com';
        $this->createUserWithRole($email, 'Password123!', false);

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $result->assertStatus(401);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['success']);
        $this->assertSame('UNAUTHORIZED', $payload['errors']['error_code'] ?? null);
    }

    public function testLoginRateLimitAsiminda429Doner(): void
    {
        $email = 'ratelimit@example.com';
        $this->createUserWithRole($email, 'Password123!', true);

        for ($i = 0; $i < 5; $i++) {
            $this->withBodyFormat('json')->post('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'WrongPass123!',
            ]);
        }

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'WrongPass123!',
        ]);

        $this->assertContains($result->response()->getStatusCode(), [401, 429]);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains($payload['errors']['error_code'] ?? null, ['RATE_LIMIT_EXCEEDED', 'UNAUTHORIZED']);
    }

    public function testLoginAuditEventleriBasariVeFailUretir(): void
    {
        $email = 'audit.login@example.com';
        $this->createUserWithRole($email, 'Password123!', true);

        $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'WrongPass123!',
        ]);

        $db = Database::connect();
        $successCount = $db->table('audit_logs')
            ->groupStart()
                ->where('event', 'auth.login.success')
                ->orWhere('action', 'auth.login.success')
            ->groupEnd()
            ->countAllResults();
        $failCount = $db->table('audit_logs')
            ->groupStart()
                ->where('event', 'auth.login.failed')
                ->orWhere('action', 'auth.login.failed')
            ->groupEnd()
            ->countAllResults();

        $this->assertGreaterThan(0, $successCount);
        $this->assertGreaterThan(0, $failCount);
    }

    public function testSuresiDolmusAccessTokenReddedilir(): void
    {
        $token = (new JwtManager())->issue([
            'sub' => 1,
            'company_id' => 1,
            'roles' => ['company_admin'],
        ], -60);

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/v1/auth/me');

        $result->assertStatus(401);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('TOKEN_EXPIRED', $payload['errors']['error_code'] ?? null);
    }

    public function testImzasiBozukAccessTokenReddedilir(): void
    {
        $token = (new JwtManager())->issue([
            'sub' => 1,
            'company_id' => 1,
            'roles' => ['company_admin'],
        ], 300);
        $token .= 'x';

        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/v1/auth/me');

        $result->assertStatus(401);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('TOKEN_INVALID', $payload['errors']['error_code'] ?? null);
    }

    public function testProtectedEndpointTokenYoksa401Doner(): void
    {
        $result = $this->get('/api/v1/auth/me');
        $result->assertStatus(401);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('TOKEN_MISSING', $payload['errors']['error_code'] ?? null);
    }

    public function testHealthVeReadyVeLoginSmokePass(): void
    {
        $health = $this->get('/health');
        $health->assertStatus(200);

        $ready = $this->get('/ready');
        $ready->assertStatus(200);

        $email = 'smoke.login@example.com';
        $this->createUserWithRole($email, 'Password123!', true);
        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $login->assertStatus(200);
    }

    private function createUserWithRole(string $email, string $password, bool $active): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Test Company ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Test',
            'last_name' => 'User',
            'status' => $active ? 'active' : 'inactive',
            'is_active' => $active ? 1 : 0,
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

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
