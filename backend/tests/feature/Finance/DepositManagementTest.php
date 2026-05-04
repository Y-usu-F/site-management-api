<?php

namespace Tests\Feature\Finance;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class DepositManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testDepositCrudAndBalanceServerSideCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('deposit1@example.com');
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'initial_amount' => 500,
            'balance_amount' => 1,
            'status' => 'cancelled',
            'notes' => 'ilk kayit',
        ]);
        $create->assertStatus(200);
        $data = json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('active', (string) $data['status']);
        $this->assertSame('500.00', number_format((float) $data['balance_amount'], 2, '.', ''));
        $this->assertStringStartsWith('DEP-', (string) $data['deposit_no']);

        $depositId = (int) $data['id'];
        $update = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/deposits/' . $depositId, [
            'notes' => 'guncel not',
        ]);
        $update->assertStatus(200);
    }

    public function testDuplicateActiveAndReceiveFlowCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('deposit2@example.com');
        $payload = [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'initial_amount' => 300,
        ];
        $first = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits', $payload);
        $first->assertStatus(200);
        $firstPayload = json_decode($first->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits', $payload)->assertStatus(409);

        $depositId = (int) $firstPayload['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/receive', [])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/receive', [])->assertStatus(409);
    }

    public function testRefundDeductApplyToDebtAndStatusesCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId, $dueItemId] = $this->bootstrapGraph('deposit3@example.com');
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'initial_amount' => 400,
        ]);
        $depositId = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/receive', [])->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/refund', ['amount' => 100])->assertStatus(200);
        $refundState = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/deposits/' . $depositId);
        $refundState->assertStatus(200);
        $this->assertSame('partially_refunded', (string) json_decode($refundState->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['status']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/deduct', ['amount' => 50])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/apply-to-debt', [
            'due_item_id' => $dueItemId,
            'amount' => 250,
        ])->assertStatus(200);

        $db = Database::connect();
        $dueItem = $db->table('due_items')->where('id', $dueItemId)->get()->getRowArray();
        $this->assertIsArray($dueItem);
        $this->assertSame('paid', (string) $dueItem['status']);
    }

    public function testApplyToDebtLockedPeriodEngeliCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId, $dueItemId] = $this->bootstrapGraph('deposit7@example.com');
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'initial_amount' => 300,
        ]);
        $depositId = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/receive', [])->assertStatus(200);

        $dueItem = Database::connect()->table('due_items')->where('id', $dueItemId)->get()->getRowArray();
        $this->assertIsArray($dueItem);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/due-periods/' . (int) $dueItem['due_period_id'] . '/lock')->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/apply-to-debt', [
            'due_item_id' => $dueItemId,
            'amount' => 50,
        ])->assertStatus(409);
    }

    public function testCancelAndCrossTenantTransactionViewCalisir(): void
    {
        [$tokenA, $siteA, $unitA, $residentA] = $this->bootstrapGraph('deposit4@example.com');
        [$tokenB] = $this->bootstrapGraph('deposit5@example.com');

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/deposits', [
            'site_id' => $siteA,
            'unit_id' => $unitA,
            'resident_profile_id' => $residentA,
            'initial_amount' => 200,
        ]);
        $depositId = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/receive', [])->assertStatus(200);
        $cancel = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/cancel', []);
        $cancel->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/deposits/' . $depositId . '/refund', ['amount' => 10])->assertStatus(409);

        $txList = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->get('/api/v1/deposits/' . $depositId . '/transactions');
        $txList->assertStatus(200);
        $txId = (int) json_decode($txList->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['items'][0]['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/deposit-transactions/' . $txId)->assertStatus(403);
    }

    public function testAuditOldValuesNewValuesYazilir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('deposit6@example.com');
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/deposits', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'initial_amount' => 250,
        ]);
        $depositId = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/deposits/' . $depositId, ['notes' => 'audit notu'])->assertStatus(200);

        $row = Database::connect()->table('audit_logs')->where('event', 'finance.deposit.update.success')->where('entity_id', (string) $depositId)->orderBy('id', 'DESC')->get(1)->getRowArray();
        $this->assertIsArray($row);
        $old = json_decode((string) ($row['old_values'] ?? '{}'), true);
        $new = json_decode((string) ($row['new_values'] ?? '{}'), true);
        $this->assertNotSame((string) ($old['notes'] ?? ''), (string) ($new['notes'] ?? ''));
    }

    /**
     * @return array{0:string,1:int,2:int,3:int,4:int}
     */
    private function bootstrapGraph(string $email): array
    {
        [$createdEmail, $userId, $companyId] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($createdEmail, 'Password123!')['data']['access_token'];

        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Dep Site', 'code' => 'DEP' . random_int(100, 999)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $siteId, 'block_id' => $blockId, 'floor_id' => $floorId, 'unit_no' => '10']);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('resident_profiles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'first_name' => 'Dep',
            'last_name' => 'Resident',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $residentId = (int) $db->insertID();
        $db->table('unit_occupancies')->insert([
            'company_id' => $companyId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'tenant',
            'start_date' => date('Y-m-d', strtotime('-1 month')),
            'end_date' => date('Y-m-d', strtotime('-1 day')),
            'status' => 'passive',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $db->table('due_periods')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'period_key' => date('Y-m'),
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-t'),
            'due_date' => date('Y-m-t'),
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $periodId = (int) $db->insertID();
        $db->table('due_definitions')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'name' => 'Aidat',
            'code' => 'AID' . random_int(100, 999),
            'calculation_type' => 'fixed',
            'amount' => 250,
            'currency' => 'TRY',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $definitionId = (int) $db->insertID();
        $db->table('due_items')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => $floorId,
            'unit_id' => $unitId,
            'due_definition_id' => $definitionId,
            'due_period_id' => $periodId,
            'description' => 'Aidat borcu',
            'amount' => 250,
            'paid_amount' => 0,
            'remaining_amount' => 250,
            'currency' => 'TRY',
            'due_date' => date('Y-m-t'),
            'status' => 'unpaid',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $dueItemId = (int) $db->insertID();

        return [$token, $siteId, $unitId, $residentId, $dueItemId];
    }

    /**
     * @return array{0:string,1:int,2:int}
     */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => 'Dep Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Dep',
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
        return [$email, $userId, $companyId];
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
