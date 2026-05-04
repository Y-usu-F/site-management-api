<?php

namespace Tests\Feature\Operation;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class MeterConsumptionManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testMeterCrudScopeVeReadingFlowCalisir(): void
    {
        [$token, $siteId, $blockId, $unitId] = $this->bootstrapGraph();

        $meter = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meters/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'unit_id' => $unitId,
            'meter_no' => 'MTR-001',
            'meter_type' => 'electricity',
            'scope' => 'unit',
        ]);
        $meter->assertStatus(200);
        $meterId = (int) json_decode($meter->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meters/', [
            'site_id' => $siteId,
            'meter_no' => 'MTR-001',
            'meter_type' => 'water',
            'scope' => 'site',
        ])->assertStatus(409);

        $period = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-reading-periods/', [
            'site_id' => $siteId,
            'period_key' => '2026-05',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'status' => 'open',
        ]);
        $period->assertStatus(200);
        $periodId = (int) json_decode($period->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $reading = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-readings/', [
            'meter_id' => $meterId,
            'reading_period_id' => $periodId,
            'previous_index' => '100.000',
            'current_index' => '130.500',
            'unit_price' => '2.1000',
            'reading_date' => '2026-05-31',
            'source' => 'admin',
            'status' => 'approved',
        ]);
        $reading->assertStatus(200);
        $readingData = json_decode($reading->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $readingId = (int) $readingData['id'];
        $this->assertSame('30.500', (string) $readingData['consumption']);
        $this->assertSame('64.05', (string) $readingData['amount']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-readings/', [
            'meter_id' => $meterId,
            'reading_period_id' => $periodId,
            'previous_index' => '130.500',
            'current_index' => '140.000',
            'reading_date' => '2026-05-31',
            'source' => 'resident',
        ])->assertStatus(409);

        $report = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/generate-consumption-report');
        $report->assertStatus(200);
        $reportData = json_decode($report->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame($unitId, (int) $reportData['unit_id']);
        $reportAgain = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/generate-consumption-report');
        $reportAgain->assertStatus(200);
        $reportAgainData = json_decode($reportAgain->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame((int) $reportData['id'], (int) $reportAgainData['id']);
    }

    public function testLockedPeriodVeStateMachineKurallariCalisir(): void
    {
        [$token, $siteId, $blockId, $unitId] = $this->bootstrapGraph('meter2@example.com');
        $meter = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meters/', [
            'site_id' => $siteId, 'block_id' => $blockId, 'unit_id' => $unitId, 'meter_type' => 'water', 'scope' => 'unit',
        ]);
        $meterId = (int) json_decode($meter->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $period = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-reading-periods/', [
            'site_id' => $siteId, 'period_key' => '2026-06', 'start_date' => '2026-06-01', 'end_date' => '2026-06-30', 'status' => 'open',
        ]);
        $periodId = (int) json_decode($period->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $reading = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-readings/', [
            'meter_id' => $meterId, 'reading_period_id' => $periodId, 'previous_index' => '10', 'current_index' => '20', 'reading_date' => '2026-06-30', 'source' => 'resident',
        ]);
        $reading->assertStatus(200);
        $readingId = (int) json_decode($reading->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/approve')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/reject')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/meter-readings/' . $readingId, [])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-reading-periods/' . $periodId . '/lock')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/approve')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/reject')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/cancel')->assertStatus(409);

        [$token2] = $this->bootstrapGraph('meter3@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])->get('/api/v1/meters/' . $meterId)->assertStatus(403);
    }

    public function testCancelledReadingUpdateEngellenir(): void
    {
        [$token, $siteId, $blockId, $unitId] = $this->bootstrapGraph('meter4@example.com');
        $meter = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meters/', [
            'site_id' => $siteId, 'block_id' => $blockId, 'unit_id' => $unitId, 'meter_type' => 'natural_gas', 'scope' => 'unit',
        ]);
        $meterId = (int) json_decode($meter->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $period = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-reading-periods/', [
            'site_id' => $siteId, 'period_key' => '2026-07', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31', 'status' => 'open',
        ]);
        $periodId = (int) json_decode($period->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $reading = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/meter-readings/', [
            'meter_id' => $meterId, 'reading_period_id' => $periodId, 'previous_index' => '5', 'current_index' => '6', 'reading_date' => '2026-07-31', 'source' => 'admin', 'status' => 'pending',
        ]);
        $readingId = (int) json_decode($reading->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/cancel')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/meter-readings/' . $readingId, ['current_index' => '7'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/meter-readings/' . $readingId . '/generate-consumption-report')->assertStatus(409);
    }

    /**
     * @return array{0:string,1:int,2:int,3:int}
     */
    private function bootstrapGraph(string $email = 'meter.user@example.com'): array
    {
        [$emailCreated] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Meter Site', 'code' => 'MTR' . random_int(10, 99)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $siteId, 'block_id' => $blockId, 'floor_id' => $floorId, 'unit_no' => '11']);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        return [$token, $siteId, $blockId, $unitId];
    }

    /**
     * @return array{0:string,1:int}
     */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(), 'name' => 'Meter Co ' . bin2hex(random_bytes(2)), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $companyId = (int) $db->insertID();
        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'Meter',
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
        $db->table('user_roles')->insert(['company_id' => $companyId, 'user_id' => $userId, 'role_id' => (int) ($role['id'] ?? 0), 'created_at' => $now, 'updated_at' => $now]);
        return [$email, $userId];
    }

    /**
     * @return array<string,mixed>
     */
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
