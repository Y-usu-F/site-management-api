<?php

namespace Tests\Feature\Audit;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuthAuditEventTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    /** @var list<string> */
    private const FORBIDDEN_META_KEYS = [
        'password',
        'password_hash',
        'token',
        'refresh_token',
        'access_token',
        'reset_token',
        'authorization',
        'cookie',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testAuthLoginSuccessAudit(): void
    {
        $email = 'audit.login.ok.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $res = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $res->assertStatus(200);
        $this->assertEnvelope($res->getJSON());

        $row = $this->fetchLatestAuditRow('auth.login.success');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthLoginFailedAudit(): void
    {
        $email = 'audit.login.fail.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'WrongPass123!',
        ])->assertStatus(401);

        $row = $this->fetchLatestAuditRow('auth.login.failed');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthLoginBlockedRateLimitAudit(): void
    {
        $email = 'audit.rl.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        for ($i = 0; $i < 5; $i++) {
            $this->withBodyFormat('json')->post('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'WrongPass123!',
            ])->assertStatus(401);
        }

        $blocked = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'WrongPass123!',
        ]);
        $blocked->assertStatus(429);
        $this->assertEnvelope($blocked->getJSON());

        $row = $this->fetchLatestAuditRow('auth.login.blocked_rate_limit');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthLoginBlockedInactiveUserAudit(): void
    {
        $email = 'audit.inactive.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', false);

        $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ])->assertStatus(401);

        $row = $this->fetchLatestAuditRow('auth.login.blocked_inactive_user');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthRefreshSuccessAudit(): void
    {
        $email = 'audit.refresh.ok.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $login->assertStatus(200);
        $body = json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $refresh = (string) ($body['data']['refresh_token'] ?? '');

        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $refresh,
        ])->assertStatus(200);

        $row = $this->fetchLatestAuditRow('auth.refresh.success');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthRefreshFailedAudit(): void
    {
        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => 'not-a-valid-jwt',
        ])->assertStatus(401);

        $row = $this->fetchLatestAuditRow('auth.refresh.failed');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthRefreshReuseDetectedAudit(): void
    {
        $email = 'audit.refresh.reuse.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $body = json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $oldRefresh = (string) ($body['data']['refresh_token'] ?? '');

        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $oldRefresh,
        ])->assertStatus(200);

        $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $oldRefresh,
        ])->assertStatus(401);

        $row = $this->fetchLatestAuditRow('auth.refresh.reuse_detected');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthLogoutSuccessAudit(): void
    {
        $email = 'audit.logout.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $body = json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $access = (string) ($body['data']['access_token'] ?? '');
        $refresh = (string) ($body['data']['refresh_token'] ?? '');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/auth/logout', ['refresh_token' => $refresh])
            ->assertStatus(200);

        $row = $this->fetchLatestAuditRow('auth.logout.success');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthForgotPasswordRequestedAudit(): void
    {
        $email = 'audit.forgot.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $this->withBodyFormat('json')->post('/api/v1/auth/forgot-password', [
            'email' => $email,
        ])->assertStatus(200);

        $row = $this->fetchLatestAuditRow('auth.forgot_password.requested');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthResetPasswordSuccessAudit(): void
    {
        $email = 'audit.reset.ok.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $userId = $this->createUserWithRole($email, 'Password123!', true);
        $plain = $this->insertPasswordResetToken($userId);

        $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => $plain,
            'password' => 'NewPass123!',
        ])->assertStatus(200);

        $row = $this->fetchLatestAuditRow('auth.reset_password.success');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testAuthResetPasswordFailedAudit(): void
    {
        $this->withBodyFormat('json')->post('/api/v1/auth/reset-password', [
            'token' => 'definitely-not-a-stored-token',
            'password' => 'NewPass123!',
        ])->assertStatus(401);

        $row = $this->fetchLatestAuditRow('auth.reset_password.failed');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testProfileUpdateSuccessAudit(): void
    {
        $email = 'audit.profile.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $body = json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $access = (string) ($body['data']['access_token'] ?? '');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->put('/api/v1/profile/', [
                'first_name' => 'AuditAd',
                'last_name' => 'AuditSoyad',
            ])
            ->assertStatus(200);

        $row = $this->fetchLatestAuditRow('profile.update.success');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testProfilePasswordChangeSuccessAudit(): void
    {
        $email = 'audit.pw.ok.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $body = json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $access = (string) ($body['data']['access_token'] ?? '');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/profile/change-password', [
                'current_password' => 'Password123!',
                'new_password' => 'NewPass123!',
            ])
            ->assertStatus(200);

        $row = $this->fetchLatestAuditRow('profile.password_change.success');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    public function testProfilePasswordChangeFailedAudit(): void
    {
        $email = 'audit.pw.fail.' . uniqid('', true) . '@example.com';
        $this->ensureDefaultCompanyAdminRole();
        $this->createUserWithRole($email, 'Password123!', true);

        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $body = json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $access = (string) ($body['data']['access_token'] ?? '');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/profile/change-password', [
                'current_password' => 'WrongPass123!',
                'new_password' => 'NewPass123!',
            ])
            ->assertStatus(401);

        $row = $this->fetchLatestAuditRow('profile.password_change.failed');
        $this->assertAuditMinimumIntegrity($row);
        $this->assertNoSensitiveInAuditPayload($row);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchLatestAuditRow(string $event): array
    {
        $db = Database::connect();
        $row = $db->table('audit_logs')
            ->groupStart()
                ->where('event', $event)
                ->orWhere('action', $event)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $this->assertIsArray($row, 'Audit kaydi bulunamadi: ' . $event);

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function assertAuditMinimumIntegrity(array $row): void
    {
        $this->assertNotEmpty($row['event'] ?? null);
        $this->assertArrayHasKey('actor_user_id', $row);
        $this->assertArrayHasKey('target_user_id', $row);
        $this->assertNotNull($row['status'] ?? null);
        $this->assertNotSame('', trim((string) ($row['status'] ?? '')));
        $this->assertArrayHasKey('ip', $row);
        $this->assertArrayHasKey('user_agent', $row);
        $this->assertNotNull($row['meta'] ?? null);
        $this->assertNotSame('', trim((string) ($row['meta'] ?? '')));
        $this->assertNotNull($row['created_at'] ?? null);
        $this->assertNotSame('', trim((string) ($row['created_at'] ?? '')));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function assertNoSensitiveInAuditPayload(array $row): void
    {
        $this->assertJsonStringHasNoSensitiveLeaks((string) ($row['meta'] ?? ''));
        foreach (['old_data', 'new_data'] as $col) {
            $raw = $row[$col] ?? null;
            if ($raw !== null && trim((string) $raw) !== '') {
                $this->assertJsonStringHasNoSensitiveLeaks((string) $raw);
            }
        }
    }

    private function assertJsonStringHasNoSensitiveLeaks(string $json): void
    {
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, 'Audit JSON parse edilemedi');
        $this->walkAssertNoSensitiveKeys($decoded);
    }

    /**
     * @param array<string, mixed>|list<mixed> $node
     */
    private function walkAssertNoSensitiveKeys(array $node): void
    {
        foreach ($node as $key => $value) {
            if (is_string($key)) {
                $norm = strtolower($key);
                if (in_array($norm, self::FORBIDDEN_META_KEYS, true)) {
                    $this->assertSame(
                        '***',
                        $value,
                        'Hassas anahtar maskelenmemis: ' . $key
                    );
                }
            }

            if (is_array($value)) {
                $this->walkAssertNoSensitiveKeys($value);
            }
        }
    }

    private function assertEnvelope(?string $json): void
    {
        $this->assertIsString($json);
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('success', $payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertIsArray($payload['meta']);
        $this->assertArrayHasKey('request_id', $payload['meta']);
    }

    private function ensureDefaultCompanyAdminRole(): void
    {
        $db = Database::connect();
        $exists = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        if ($exists !== null) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $db->table('roles')->insert([
            'company_id' => null,
            'code' => 'company_admin',
            'name' => 'Company Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createUserWithRole(string $email, string $password, bool $active): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Audit Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Audit',
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
        $roleId = (int) ($role['id'] ?? 0);
        $this->assertGreaterThan(0, $roleId);

        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $userId;
    }

    private function insertPasswordResetToken(int $userId): string
    {
        $plain = bin2hex(random_bytes(32));
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('password_reset_tokens')->insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'used_at' => null,
            'requested_ip' => '127.0.0.1',
            'requested_user_agent' => 'phpunit',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $plain;
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
