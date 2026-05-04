<?php

namespace Tests\Feature\Rbac;

use App\Exceptions\PermissionNotFoundException;
use App\Libraries\Auth\JwtManager;
use App\Services\Auth\AuthorizationService;
use App\Services\Auth\PermissionService;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class RbacTenantScopeTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testCompanyAdminKendiTenantindaCalisirTenantDisindaDenyOlur(): void
    {
        [$userId, $companyId] = $this->createActiveUser('tenant.company.admin@example.com');
        $roleId = $this->createRole('company_admin');
        $this->assignRole($userId, $companyId, $roleId, true, 3);
        $this->attachPermissionToRole($roleId, 'auth.session.list', true);

        $token = $this->issueToken($userId, $companyId, ['company_admin']);
        $allow = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Request-Id' => 'tenant-allow',
        ])->get('/api/v1/auth/sessions');
        $allow->assertStatus(200);

        $service = new AuthorizationService();
        $deny = $service->authorize($userId, $companyId, 'auth.session.list', $companyId + 10);
        $this->assertFalse($deny['allowed']);
        $this->assertSame('tenant_mismatch', $deny['reason']);
    }

    public function testAyniKullaniciFarkliCompanyContextteFarkliPermissionSetiAlir(): void
    {
        [$userId, $companyIdA] = $this->createActiveUser('tenant.multi.company@example.com');
        $companyIdB = $this->createCompany();

        $roleA = $this->createRole('employee');
        $roleB = $this->createRole('profile_reader');

        $this->assignRole($userId, $companyIdA, $roleA, true, 1);
        $this->assignRole($userId, $companyIdB, $roleB, true, 1);
        $this->attachPermissionToRole($roleA, 'auth.session.list', true);
        $this->attachPermissionToRole($roleB, 'profile.view', true);

        $permissionService = new PermissionService();
        $codesA = $permissionService->getPermissionCodesForUser($userId, $companyIdA);
        $codesB = $permissionService->getPermissionCodesForUser($userId, $companyIdB);

        $this->assertContains('auth.session.list', $codesA);
        $this->assertNotContains('profile.view', $codesA);
        $this->assertContains('profile.view', $codesB);
        $this->assertNotContains('auth.session.list', $codesB);
    }

    public function testRouteUzerindeTanimsizPermissionFailFastYakalanir(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $service = new AuthorizationService();
        $service->authorize(10, 20, 'non.existing.permission', 20);
    }

    private function createActiveUser(string $email): array
    {
        $db = Database::connect();
        $companyId = $this->createCompany();

        $now = date('Y-m-d H:i:s');
        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'first_name' => 'Tenant',
            'last_name' => 'User',
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [(int) $db->insertID(), $companyId];
    }

    private function createCompany(): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Tenant Co ' . bin2hex(random_bytes(2)),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $db->insertID();
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

    private function assignRole(int $userId, int $companyId, int $roleId, bool $active, int $version): void
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'is_active' => $active ? 1 : 0,
            'role_version' => $version,
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
        }

        $db->table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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

