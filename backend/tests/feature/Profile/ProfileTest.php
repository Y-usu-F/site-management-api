<?php

namespace Tests\Feature\Profile;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class ProfileTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testTokenYokkenGetProfile401Doner(): void
    {
        $result = $this->get('/api/v1/profile/');
        $result->assertStatus(401);
    }

    public function testGecerliTokenIleGetProfileStandartZarfDoner(): void
    {
        [$email] = $this->createUserWithRole('profile.get@example.com', 'Password123!');
        $login = $this->login($email, 'Password123!');
        $access = (string) $login['data']['access_token'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/profile/');
        $result->assertStatus(200);
        $payload = json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('request_id', $payload['meta']);
    }

    public function testPutProfileSadeceIzinliAlanlariGuncellerVeWhitelistDisiniDegistirmez(): void
    {
        [$email, $userId] = $this->createUserWithRole('profile.put@example.com', 'Password123!');
        $login = $this->login($email, 'Password123!');
        $access = (string) $login['data']['access_token'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->put('/api/v1/profile/', [
                'first_name' => 'YeniAd',
                'last_name' => 'YeniSoyad',
                'email' => 'hacker@example.com',
                'status' => 'inactive',
                'is_active' => 0,
            ]);
        $result->assertStatus(200);

        $db = Database::connect();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertSame('YeniAd', $user['first_name'] ?? null);
        $this->assertSame('YeniSoyad', $user['last_name'] ?? null);
        $this->assertSame($email, $user['email'] ?? null);
        $this->assertSame('active', $user['status'] ?? null);
        $this->assertSame(1, (int) ($user['is_active'] ?? 0));
    }

    public function testProfileUpdateAuditEventYazilir(): void
    {
        [$email] = $this->createUserWithRole('profile.audit@example.com', 'Password123!');
        $login = $this->login($email, 'Password123!');
        $access = (string) $login['data']['access_token'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->put('/api/v1/profile/', [
                'first_name' => 'Audit',
                'last_name' => 'Profile',
            ])
            ->assertStatus(200);

        $db = Database::connect();
        $count = $db->table('audit_logs')->where('event', 'profile.update.success')->countAllResults();
        $this->assertGreaterThan(0, $count);
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
            'name' => 'Profile Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Profile',
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
