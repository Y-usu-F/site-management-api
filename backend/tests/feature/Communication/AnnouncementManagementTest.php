<?php

namespace Tests\Feature\Communication;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AnnouncementManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testAnnouncementCrudVeStateKurallari(): void
    {
        [$token, $siteId] = $this->bootstrapTenant('ann1@example.com');
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/announcements/', [
            'title' => 'Duyuru',
            'body' => 'Test body',
            'publish_at' => '2026-12-01 10:00:00',
            'expires_at' => '2026-12-02 10:00:00',
            'targets' => [['target_type' => 'site', 'target_id' => (string) $siteId]],
        ]);
        $create->assertStatus(200);
        $id = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/archive')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/publish')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/publish')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/announcements/' . $id, ['title' => 'Yeni'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/archive')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/cancel')->assertStatus(409);
    }

    public function testTargetTypeKurallariVeTenantConsistency(): void
    {
        [$token, $siteId] = $this->bootstrapTenant('ann2@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/announcements/', [
            'title' => 'All',
            'body' => 'Body',
            'targets' => [['target_type' => 'all', 'target_id' => '12']],
        ])->assertStatus(409);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/announcements/', [
            'title' => 'Site',
            'body' => 'Body',
            'targets' => [['target_type' => 'site', 'target_id' => (string) $siteId]],
        ])->assertStatus(200);
    }

    public function testExpiresAtPublishAtKontroluVeMarkReadKurallari(): void
    {
        [$token, $siteId] = $this->bootstrapTenant('ann3@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/announcements/', [
            'title' => 'Bad Date',
            'body' => 'Body',
            'publish_at' => '2026-12-03 10:00:00',
            'expires_at' => '2026-12-01 10:00:00',
            'targets' => [['target_type' => 'site', 'target_id' => (string) $siteId]],
        ])->assertStatus(409);

        $ok = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/announcements/', [
            'title' => 'Read Test',
            'body' => 'Body',
            'targets' => [['target_type' => 'site', 'target_id' => (string) $siteId]],
        ]);
        $id = (int) json_decode($ok->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/mark-read')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/publish')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/mark-read')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/announcements/' . $id . '/mark-read')->assertStatus(200);
    }

    public function testCrossTenant403VeReadsTargetsList(): void
    {
        [$tokenA, $siteA] = $this->bootstrapTenant('ann4a@example.com');
        [$tokenB] = $this->bootstrapTenant('ann4b@example.com');
        $created = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/announcements/', [
            'title' => 'Cross',
            'body' => 'Body',
            'targets' => [['target_type' => 'site', 'target_id' => (string) $siteA]],
        ]);
        $id = (int) json_decode($created->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->post('/api/v1/announcements/' . $id . '/publish')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->post('/api/v1/announcements/' . $id . '/mark-read')->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->get('/api/v1/announcements/' . $id . '/reads')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->get('/api/v1/announcements/' . $id . '/targets')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/announcements/' . $id)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->post('/api/v1/announcements/' . $id . '/mark-read')->assertStatus(403);
    }

    /**
     * @return array{0:string,1:int}
     */
    private function bootstrapTenant(string $email): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Ann Co ' . bin2hex(random_bytes(2)),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = (int) $db->insertID();
        $password = 'Password123!';
        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'Ann',
            'last_name' => 'Admin',
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 0,
            'locked_until' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int) $db->insertID();
        $role = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => (int) ($role['id'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $login = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        $login->assertStatus(200);
        $token = (string) json_decode($login->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['access_token'];

        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', [
            'name' => 'Site',
            'code' => 'S' . strtoupper(bin2hex(random_bytes(4))),
        ]);
        $site->assertStatus(200);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        return [$token, $siteId];
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
