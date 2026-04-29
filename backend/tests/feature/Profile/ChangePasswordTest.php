<?php

namespace Tests\Feature\Profile;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class ChangePasswordTest extends CIUnitTestCase
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

    public function testEskiSifreYanlissaDegisimOlmazVeFailAuditYazar(): void
    {
        [$email, $userId] = $this->createUserWithRole('change.fail@example.com', 'Password123!');
        $login = $this->login($email, 'Password123!');
        $access = (string) $login['data']['access_token'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/profile/change-password', [
                'current_password' => 'WrongPass123!',
                'new_password' => 'NewPass123!',
            ]);
        $result->assertStatus(401);

        $db = Database::connect();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertTrue(password_verify('Password123!', (string) ($user['password_hash'] ?? '')));
        $this->assertGreaterThan(0, $db->table('audit_logs')->where('event', 'profile.password_change.failed')->countAllResults());
    }

    public function testYeniSifreEskiyleAyniysaReddedilir(): void
    {
        [$email] = $this->createUserWithRole('change.same@example.com', 'Password123!');
        $login = $this->login($email, 'Password123!');
        $access = (string) $login['data']['access_token'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/profile/change-password', [
                'current_password' => 'Password123!',
                'new_password' => 'Password123!',
            ]);
        $result->assertStatus(422);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['success']);
        $this->assertSame('VALIDATION_ERROR', $payload['errors']['error_code'] ?? null);
    }

    public function testGecerliDegisimdePasswordGuncellenirSessionPolitikasiVeAuditUygulanir(): void
    {
        [$email, $userId] = $this->createUserWithRole('change.success@example.com', 'Password123!');
        $loginOne = $this->login($email, 'Password123!');
        $loginTwo = $this->login($email, 'Password123!');
        $access = (string) $loginOne['data']['access_token'];
        $this->assertNotEmpty($loginTwo['data']['refresh_token'] ?? null);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/profile/change-password', [
                'current_password' => 'Password123!',
                'new_password' => 'NewPass123!',
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
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertTrue(password_verify('NewPass123!', (string) ($user['password_hash'] ?? '')));

        $revoked = $db->table('user_refresh_tokens')
            ->where('user_id', $userId)
            ->where('revoked_reason', 'password_changed')
            ->countAllResults();
        $this->assertGreaterThan(0, $revoked);

        $this->assertGreaterThan(0, $db->table('audit_logs')->where('event', 'profile.password_change.success')->countAllResults());
    }

    /**
     * @return array{0:string,1:int}
     */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Change Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Change',
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

        return [$email, $userId];
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
