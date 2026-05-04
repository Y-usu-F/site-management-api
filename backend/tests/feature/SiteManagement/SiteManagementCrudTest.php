<?php

namespace Tests\Feature\SiteManagement;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class SiteManagementCrudTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testCrudFlowSitesBlocksFloorsUnitsCalisir(): void
    {
        [$email] = $this->createUserWithRole('sitemodule@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/sites/', [
            'name' => 'A Site',
            'code' => 'SITE-A',
            'address' => 'Adres',
        ]);
        $site->assertStatus(200);
        $siteData = json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $siteId = (int) $siteData['id'];

        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/blocks/', [
            'site_id' => $siteId,
            'name' => 'A Blok',
            'code' => 'A',
        ]);
        $block->assertStatus(200);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/floors/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'number' => 1,
            'label' => 'Zemin',
        ]);
        $floor->assertStatus(200);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/units/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => $floorId,
            'unit_no' => '1',
            'type' => 'daire',
        ]);
        $unit->assertStatus(200);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/sites/?search=site')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/blocks/?site_id=' . $siteId)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/floors/?block_id=' . $blockId)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/units/?floor_id=' . $floorId)->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->put('/api/v1/units/' . $unitId, ['occupant_name' => 'Kiraci'])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->delete('/api/v1/units/' . $unitId)->assertStatus(200);

        $db = Database::connect();
        $auditCount = $db->table('audit_logs')->groupStart()
            ->like('event', 'site.site.')
            ->orLike('event', 'site.block.')
            ->orLike('event', 'site.floor.')
            ->orLike('event', 'site.unit.')
            ->groupEnd()
            ->countAllResults();
        $this->assertGreaterThan(0, $auditCount);
    }

    public function testAyniSiteIcindeBlokAdiUniqueOlmali(): void
    {
        [$email] = $this->createUserWithRole('block.unique@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $siteId = $this->createSiteViaApi($access, 'Unique Site', 'US-1');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'A Blok', 'code' => 'A1'])
            ->assertStatus(200);

        $duplicate = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'A Blok', 'code' => 'A2']);
        $duplicate->assertStatus(409);
    }

    public function testAyniKattaUnitNoUniqueOlmali(): void
    {
        [$email] = $this->createUserWithRole('unit.unique@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$siteId, $blockId, $floorId] = $this->createSiteGraph($access, 'U Site', 'US-2');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/units/', [
                'site_id' => $siteId,
                'block_id' => $blockId,
                'floor_id' => $floorId,
                'unit_no' => '10',
                'type' => 'daire',
            ])
            ->assertStatus(200);

        $dup = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/units/', [
                'site_id' => $siteId,
                'block_id' => $blockId,
                'floor_id' => $floorId,
                'unit_no' => '10',
                'type' => 'daire',
            ]);
        $dup->assertStatus(409);
    }

    public function testSoftDeleteSonrasiSiteListedeYokturVeCocukEklenemez(): void
    {
        [$email] = $this->createUserWithRole('soft.delete@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $siteId = $this->createSiteViaApi($access, 'Soft Site', 'SS-1');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->delete('/api/v1/sites/' . $siteId)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/sites/' . $siteId)->assertStatus(404);

        $list = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/sites/?search=Soft Site');
        $list->assertStatus(200);
        $payload = json_decode($list->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $items = $payload['data']['items'] ?? [];
        $matched = array_filter($items, static fn (array $item): bool => (int) ($item['id'] ?? 0) === $siteId);
        $this->assertSame([], array_values($matched));

        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'X Blok', 'code' => 'X']);
        $block->assertStatus(404);
    }

    public function testCrossTenantErisimi403Doner(): void
    {
        [$ownerEmail] = $this->createUserWithRole('owner@example.com', 'Password123!');
        [$attackerEmail] = $this->createUserWithRole('attacker@example.com', 'Password123!');

        $ownerAccess = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $attackerAccess = (string) $this->login($attackerEmail, 'Password123!')['data']['access_token'];
        $siteId = $this->createSiteViaApi($ownerAccess, 'Tenant Site', 'TS-1');

        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->get('/api/v1/sites/' . $siteId)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])
            ->withBodyFormat('json')
            ->put('/api/v1/sites/' . $siteId, ['name' => 'Hack'])
            ->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->delete('/api/v1/sites/' . $siteId)->assertStatus(403);
    }

    public function testAuditOldValuesVeNewValuesDoluYazilir(): void
    {
        [$email] = $this->createUserWithRole('audit.values@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $siteId = $this->createSiteViaApi($access, 'Audit Site', 'AS-1');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->put('/api/v1/sites/' . $siteId, ['name' => 'Audit Site Updated'])
            ->assertStatus(200);

        $db = Database::connect();
        $row = $db->table('audit_logs')
            ->where('event', 'site.site.update.success')
            ->where('entity_id', (string) $siteId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $this->assertNotNull($row);
        $old = json_decode((string) ($row['old_values'] ?? '{}'), true);
        $new = json_decode((string) ($row['new_values'] ?? '{}'), true);
        $this->assertIsArray($old);
        $this->assertIsArray($new);
        $this->assertSame('Audit Site', $old['name'] ?? null);
        $this->assertSame('Audit Site Updated', $new['name'] ?? null);
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
            'name' => 'Site Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Site',
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

    private function login(string $email, string $password): array
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        $result->assertStatus(200);
        return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createSiteViaApi(string $accessToken, string $name, string $code): int
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
            ->withBodyFormat('json')
            ->post('/api/v1/sites/', ['name' => $name, 'code' => $code, 'address' => 'Adres']);
        $response->assertStatus(200);

        return (int) json_decode($response->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function createSiteGraph(string $accessToken, string $siteName, string $siteCode): array
    {
        $siteId = $this->createSiteViaApi($accessToken, $siteName, $siteCode);
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
            ->withBodyFormat('json')
            ->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'A Blok', 'code' => 'A']);
        $block->assertStatus(200);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
            ->withBodyFormat('json')
            ->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1, 'label' => '1']);
        $floor->assertStatus(200);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        return [$siteId, $blockId, $floorId];
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
