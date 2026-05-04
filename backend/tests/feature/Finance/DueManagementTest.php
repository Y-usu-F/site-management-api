<?php

namespace Tests\Feature\Finance;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class DueManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testPeriodKeyFormatValidation(): void
    {
        [$email] = $this->createUserWithRole('due.period.format@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [, $siteId] = $this->createUnitGraph($token, 'DUE-S1', 2);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-periods/', [
            'site_id' => $siteId,
            'period_key' => '2026/05',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'due_date' => '2026-05-10',
        ]);
        $result->assertStatus(422);
    }

    public function testDuplicateSitePeriodKeyEngellenir(): void
    {
        [$email] = $this->createUserWithRole('due.period.dup@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [, $siteId] = $this->createUnitGraph($token, 'DUE-S2', 2);

        $payload = [
            'site_id' => $siteId,
            'period_key' => '2026-05',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'due_date' => '2026-05-10',
            'status' => 'draft',
        ];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-periods/', $payload)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-periods/', $payload)->assertStatus(409);
    }

    public function testLockedPeriodUpdateDeleteTahakkukEngellenir(): void
    {
        [$email] = $this->createUserWithRole('due.period.lock@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitIds, $siteId] = $this->createUnitGraph($token, 'DUE-S3', 2);
        $periodId = $this->createPeriod($token, $siteId, '2026-06');
        $definitionId = $this->createDefinition($token, $siteId, null, 'fixed', 100);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/due-periods/' . $periodId . '/lock')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/due-periods/' . $periodId, ['due_date' => '2026-06-15'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete('/api/v1/due-periods/' . $periodId)->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
            'due_definition_id' => $definitionId,
            'due_period_id' => $periodId,
        ])->assertStatus(409);
    }

    public function testFixedAndBlockScopedAndIdempotentBatch(): void
    {
        [$email] = $this->createUserWithRole('due.batch.fixed@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitIds, $siteId, $blockId] = $this->createUnitGraph($token, 'DUE-S4', 3);
        $periodId = $this->createPeriod($token, $siteId, '2026-07');

        $defSite = $this->createDefinition($token, $siteId, null, 'fixed', 50);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
            'due_definition_id' => $defSite,
            'due_period_id' => $periodId,
        ])->assertStatus(200);

        $db = Database::connect();
        $siteCount = $db->table('due_items')->where('due_definition_id', $defSite)->where('due_period_id', $periodId)->where('deleted_at', null)->countAllResults();
        $this->assertSame(3, $siteCount);

        // idempotent rerun
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
            'due_definition_id' => $defSite,
            'due_period_id' => $periodId,
        ])->assertStatus(200);
        $siteCount2 = $db->table('due_items')->where('due_definition_id', $defSite)->where('due_period_id', $periodId)->where('deleted_at', null)->countAllResults();
        $this->assertSame(3, $siteCount2);

        $defBlock = $this->createDefinition($token, $siteId, $blockId, 'fixed', 30);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
            'due_definition_id' => $defBlock,
            'due_period_id' => $periodId,
        ])->assertStatus(200);
        $blockCount = $db->table('due_items')->where('due_definition_id', $defBlock)->where('due_period_id', $periodId)->where('deleted_at', null)->countAllResults();
        $this->assertSame(3, $blockCount);
    }

    public function testUnitAreaLandShareResidentCountVePaymentStatus(): void
    {
        [$email] = $this->createUserWithRole('due.calc@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitIds, $siteId] = $this->createUnitGraph($token, 'DUE-S5', 1, 10, 12, 0.5);
        $unitId = $unitIds[0];
        $periodId = $this->createPeriod($token, $siteId, '2026-08');
        $residentId = $this->createResident($token, 'Calc', 'Person');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'resident',
            'start_date' => '2026-08-01',
            'status' => 'active',
        ])->assertStatus(200);

        $defArea = $this->createDefinition($token, $siteId, null, 'unit_area', 10);
        $this->runBatch($token, $defArea, $periodId);
        $this->assertDueAmount($defArea, $periodId, 100.00);

        $defShare = $this->createDefinition($token, $siteId, null, 'land_share', 200);
        $this->runBatch($token, $defShare, $periodId);
        $this->assertDueAmount($defShare, $periodId, 100.00);

        $defResident = $this->createDefinition($token, $siteId, null, 'resident_count', 150);
        $this->runBatch($token, $defResident, $periodId);
        $this->assertDueAmount($defResident, $periodId, 150.00);

        $db = Database::connect();
        $item = $db->table('due_items')->where('due_definition_id', $defResident)->where('due_period_id', $periodId)->get()->getRowArray();
        $itemId = (int) $item['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/due-items/' . $itemId, ['paid_amount' => 50])->assertStatus(200);
        $row = $db->table('due_items')->where('id', $itemId)->get()->getRowArray();
        $this->assertSame('partial', $row['status']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/due-items/' . $itemId, ['paid_amount' => 150])->assertStatus(200);
        $row = $db->table('due_items')->where('id', $itemId)->get()->getRowArray();
        $this->assertSame('paid', $row['status']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/due-items/' . $itemId, ['paid_amount' => 160])->assertStatus(409);
    }

    public function testCancelledDueItemUpdateEdilemezVeCrossTenant403(): void
    {
        [$emailA] = $this->createUserWithRole('due.cross.a@example.com', 'Password123!');
        [$emailB] = $this->createUserWithRole('due.cross.b@example.com', 'Password123!');
        $tokenA = (string) $this->login($emailA, 'Password123!')['data']['access_token'];
        $tokenB = (string) $this->login($emailB, 'Password123!')['data']['access_token'];
        [$unitIds, $siteId] = $this->createUnitGraph($tokenA, 'DUE-S6', 1);
        $periodId = $this->createPeriod($tokenA, $siteId, '2026-09');
        $def = $this->createDefinition($tokenA, $siteId, null, 'fixed', 40);
        $this->runBatch($tokenA, $def, $periodId);

        $db = Database::connect();
        $item = $db->table('due_items')->where('due_definition_id', $def)->where('due_period_id', $periodId)->get()->getRowArray();
        $itemId = (int) $item['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->post('/api/v1/due-items/' . $itemId . '/cancel')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->put('/api/v1/due-items/' . $itemId, ['paid_amount' => 10])->assertStatus(409);

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/due-definitions/' . $def)->assertStatus(403);

        $auditCount = $db->table('audit_logs')->where('event', 'finance.due_item.cancel.success')->countAllResults();
        $this->assertGreaterThan(0, $auditCount);
    }

    public function testLockedPerioddeDueItemUpdateCancelEngellenir(): void
    {
        [$email] = $this->createUserWithRole('due.item.locked@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitIds, $siteId] = $this->createUnitGraph($token, 'DUE-S7', 1);
        $periodId = $this->createPeriod($token, $siteId, '2026-10');
        $def = $this->createDefinition($token, $siteId, null, 'fixed', 70);
        $this->runBatch($token, $def, $periodId);

        $db = Database::connect();
        $item = $db->table('due_items')->where('due_definition_id', $def)->where('due_period_id', $periodId)->get()->getRowArray();
        $itemId = (int) $item['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/due-periods/' . $periodId . '/lock')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/due-items/' . $itemId, ['paid_amount' => 10])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/due-items/' . $itemId . '/cancel')->assertStatus(409);
    }

    private function runBatch(string $token, int $definitionId, int $periodId): void
    {
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
            'due_definition_id' => $definitionId,
            'due_period_id' => $periodId,
        ])->assertStatus(200);
    }

    private function assertDueAmount(int $definitionId, int $periodId, float $expected): void
    {
        $db = Database::connect();
        $row = $db->table('due_items')->where('due_definition_id', $definitionId)->where('due_period_id', $periodId)->orderBy('id', 'DESC')->get(1)->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame(number_format($expected, 2, '.', ''), number_format((float) $row['amount'], 2, '.', ''));
    }

    private function createDefinition(string $token, int $siteId, ?int $blockId, string $type, float $amount): int
    {
        $payload = [
            'site_id' => $siteId,
            'name' => 'DEF ' . $type . ' ' . $siteId,
            'calculation_type' => $type,
            'amount' => $amount,
            'currency' => 'TRY',
            'status' => 'active',
        ];
        if ($blockId !== null) {
            $payload['block_id'] = $blockId;
        }
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-definitions/', $payload);
        $res->assertStatus(200);
        return (int) json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
    }

    private function createPeriod(string $token, int $siteId, string $periodKey): int
    {
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-periods/', [
            'site_id' => $siteId,
            'period_key' => $periodKey,
            'start_date' => substr($periodKey, 0, 4) . '-' . substr($periodKey, 5, 2) . '-01',
            'end_date' => substr($periodKey, 0, 4) . '-' . substr($periodKey, 5, 2) . '-28',
            'due_date' => substr($periodKey, 0, 4) . '-' . substr($periodKey, 5, 2) . '-15',
            'status' => 'open',
        ]);
        $res->assertStatus(200);
        return (int) json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
    }

    private function createResident(string $token, string $first, string $last): int
    {
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/residents/', [
            'first_name' => $first,
            'last_name' => $last,
            'status' => 'active',
        ]);
        $res->assertStatus(200);
        return (int) json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
    }

    /**
     * @return array{0:list<int>,1:int,2:int}
     */
    private function createUnitGraph(string $token, string $siteCode, int $unitCount, float $netArea = 10, float $grossArea = 12, float $landShare = 0.5): array
    {
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', [
            'name' => 'Due Site ' . $siteCode,
            'code' => $siteCode,
            'status' => 'active',
        ]);
        $site->assertStatus(200);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', [
            'site_id' => $siteId,
            'name' => 'A',
            'code' => 'A',
            'status' => 'active',
        ]);
        $block->assertStatus(200);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'number' => 1,
            'label' => '1',
            'status' => 'active',
        ]);
        $floor->assertStatus(200);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $db = Database::connect();
        $unitIds = [];
        for ($i = 1; $i <= $unitCount; $i++) {
            $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', [
                'site_id' => $siteId,
                'block_id' => $blockId,
                'floor_id' => $floorId,
                'unit_no' => (string) $i,
                'net_area' => $netArea,
                'gross_area' => $grossArea,
                'status' => 'active',
            ]);
            $unit->assertStatus(200);
            $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
            $unitIds[] = $unitId;
            $db->table('units')->where('id', $unitId)->update(['land_share' => $landShare]);
        }

        return [$unitIds, $siteId, $blockId];
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
            'name' => 'Due Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Due',
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
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => (int) ($role['id'] ?? 0),
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

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}
