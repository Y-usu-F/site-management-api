<?php

namespace Tests\Feature\Operation;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AssetMaintenanceManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;


    public function testAssetPlanRecordFlowCalisir(): void
    {
        [$token, $siteId, $blockId, $unitId] = $this->bootstrapGraph();
        $asset = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/assets/', [
            'site_id' => $siteId, 'block_id' => $blockId, 'unit_id' => $unitId, 'asset_no' => 'AST-1', 'asset_type' => 'elevator', 'name' => 'Asansor 1', 'status' => 'active',
        ]);
        $asset->assertStatus(200);
        $assetId = (int) json_decode($asset->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/assets/', [
            'site_id' => $siteId, 'asset_no' => 'AST-1', 'asset_type' => 'camera', 'name' => 'Kamera',
        ])->assertStatus(409);
        $plan = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-plans/', [
            'asset_id' => $assetId, 'frequency_type' => 'monthly', 'frequency_interval' => 1, 'next_due_date' => date('Y-m-d', strtotime('+3 day')),
        ]);
        $plan->assertStatus(200);
        $planId = (int) json_decode($plan->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/asset-maintenance-plans/' . $planId . '/pause')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/asset-maintenance-plans/' . $planId . '/resume')->assertStatus(200);
        $record = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-records/', [
            'asset_id' => $assetId, 'maintenance_plan_id' => $planId, 'performed_at' => date('Y-m-d H:i:s'), 'cost_amount' => '100.50',
        ]);
        $record->assertStatus(200);
        $recordId = (int) json_decode($record->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/asset-maintenance-records/' . $recordId . '/cancel')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/asset-maintenance-records/' . $recordId . '/cancel')->assertStatus(409);
    }

    public function testRetiredVeCrossTenantKurallariCalisir(): void
    {
        [$token, $siteId, $blockId, $unitId] = $this->bootstrapGraph('asset2@example.com');
        $asset = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/assets/', [
            'site_id' => $siteId, 'block_id' => $blockId, 'unit_id' => $unitId, 'asset_type' => 'generator', 'name' => 'Jenerator', 'status' => 'retired',
        ]);
        $asset->assertStatus(200);
        $assetId = (int) json_decode($asset->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/assets/' . $assetId, ['name' => 'x'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-plans/', [
            'asset_id' => $assetId, 'frequency_type' => 'monthly', 'frequency_interval' => 1, 'next_due_date' => date('Y-m-d', strtotime('+2 day')),
        ])->assertStatus(409);
        [$token2] = $this->bootstrapGraph('asset3@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])->get('/api/v1/assets/' . $assetId)->assertStatus(403);
    }

    public function testValidationVeRecordIliskiKurallariCalisir(): void
    {
        [$token, $siteId, $blockId, $unitId] = $this->bootstrapGraph('asset4@example.com');
        $asset1 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/assets/', [
            'site_id' => $siteId, 'block_id' => $blockId, 'unit_id' => $unitId, 'asset_type' => 'camera', 'name' => 'Cam-1', 'serial_number' => 'SER-1',
        ]);
        $asset1->assertStatus(200);
        $asset1Id = (int) json_decode($asset1->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        // nullable serial duplicate allowed, non-null duplicate blocked for active assets
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/assets/', [
            'site_id' => $siteId, 'asset_type' => 'camera', 'name' => 'Cam-2', 'serial_number' => 'SER-1',
        ])->assertStatus(409);

        $asset2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/assets/', [
            'site_id' => $siteId, 'asset_type' => 'other', 'name' => 'NoSerial',
        ]);
        $asset2->assertStatus(200);
        $asset2Id = (int) json_decode($asset2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        // unit verilince block zorunlu
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/assets/' . $asset2Id, [
            'unit_id' => $unitId,
            'block_id' => null,
        ])->assertStatus(409);

        // plan validations
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-plans/', [
            'asset_id' => $asset1Id, 'frequency_type' => 'monthly', 'frequency_interval' => 0, 'next_due_date' => date('Y-m-d', strtotime('+1 day')),
        ])->assertStatus(422);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-plans/', [
            'asset_id' => $asset1Id, 'frequency_type' => 'monthly', 'frequency_interval' => 1, 'next_due_date' => date('Y-m-d', strtotime('-1 day')),
        ])->assertStatus(409);

        $plan = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-plans/', [
            'asset_id' => $asset1Id, 'frequency_type' => 'monthly', 'frequency_interval' => 1, 'next_due_date' => date('Y-m-d', strtotime('+5 day')),
        ]);
        $plan->assertStatus(200);
        $planId = (int) json_decode($plan->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/asset-maintenance-plans/' . $planId . '/cancel')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/asset-maintenance-plans/' . $planId, ['notes' => 'x'])->assertStatus(409);

        // relationship checks: plan belongs to different asset
        $plan2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-plans/', [
            'asset_id' => $asset2Id, 'frequency_type' => 'monthly', 'frequency_interval' => 1, 'next_due_date' => date('Y-m-d', strtotime('+6 day')),
        ]);
        $plan2Id = (int) json_decode($plan2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-records/', [
            'asset_id' => $asset1Id, 'maintenance_plan_id' => $plan2Id, 'performed_at' => date('Y-m-d H:i:s'),
        ])->assertStatus(409);

        // negative cost
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/asset-maintenance-records/', [
            'asset_id' => $asset1Id, 'performed_at' => date('Y-m-d H:i:s'), 'cost_amount' => -1,
        ])->assertStatus(409);
    }

    /** @return array{0:string,1:int,2:int,3:int} */
    private function bootstrapGraph(string $email = 'asset.user@example.com'): array
    {
        [$emailCreated] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Asset Site', 'code' => 'AST' . random_int(10, 99)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $siteId, 'block_id' => $blockId, 'floor_id' => $floorId, 'unit_no' => '11']);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        return [$token, $siteId, $blockId, $unitId];
    }

    /** @return array{0:string,1:int} */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(), 'name' => 'Asset Co ' . bin2hex(random_bytes(2)), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $companyId = (int) $db->insertID();
        $db->table('users')->insert(['company_id' => $companyId, 'public_id' => $this->uuid(), 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'first_name' => 'Asset', 'last_name' => 'User', 'status' => 'active', 'is_active' => 1, 'failed_login_count' => 0, 'locked_until' => null, 'created_at' => $now, 'updated_at' => $now]);
        $userId = (int) $db->insertID();
        $role = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        $db->table('user_roles')->insert(['company_id' => $companyId, 'user_id' => $userId, 'role_id' => (int) ($role['id'] ?? 0), 'created_at' => $now, 'updated_at' => $now]);
        return [$email, $userId];
    }

    /** @return array<string,mixed> */
    private function login(string $email, string $password): array
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
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
