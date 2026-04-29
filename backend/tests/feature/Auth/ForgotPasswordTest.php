<?php

namespace Tests\Feature\Auth;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class ForgotPasswordTest extends CIUnitTestCase
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

    public function testKullaniciVarsaResponseVarlikSizdirmaz(): void
    {
        $email = 'forgot.exists@example.com';
        $this->createUserWithRole($email, 'Password123!');

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/forgot-password', [
            'email' => $email,
        ]);
        $result->assertStatus(200);

        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success']);
        $this->assertSame('if_account_exists', $payload['data']['delivery'] ?? null);
        $this->assertArrayHasKey('request_id', $payload['meta']);
    }

    public function testKullaniciYoksaResponseVarlikSizdirmazVeModelAynidir(): void
    {
        $this->createUserWithRole('forgot.same.exists@example.com', 'Password123!');

        $exists = $this->withBodyFormat('json')->post('/api/v1/auth/forgot-password', [
            'email' => 'forgot.same.exists@example.com',
        ]);

        $missing = $this->withBodyFormat('json')->post('/api/v1/auth/forgot-password', [
            'email' => 'forgot.same.missing@example.com',
        ]);

        $exists->assertStatus(200);
        $missing->assertStatus(200);

        $existsPayload = json_decode($exists->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $missingPayload = json_decode($missing->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($existsPayload['success'], $missingPayload['success']);
        $this->assertSame($existsPayload['message'], $missingPayload['message']);
        $this->assertSame($existsPayload['data']['accepted'] ?? null, $missingPayload['data']['accepted'] ?? null);
        $this->assertSame($existsPayload['data']['delivery'] ?? null, $missingPayload['data']['delivery'] ?? null);
    }

    public function testForgotPasswordAuditEventYazilir(): void
    {
        $email = 'forgot.audit@example.com';
        $this->createUserWithRole($email, 'Password123!');

        $this->withBodyFormat('json')->post('/api/v1/auth/forgot-password', [
            'email' => $email,
        ])->assertStatus(200);

        $db = Database::connect();
        $count = $db->table('audit_logs')->where('event', 'auth.forgot_password.requested')->countAllResults();
        $this->assertGreaterThan(0, $count);
    }

    private function createUserWithRole(string $email, string $password): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Forgot Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Forgot',
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
