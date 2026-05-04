<?php

namespace Tests\Feature\Finance;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class PaymentManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testManualPaymentOlusturulurVePaymentNoUnique(): void
    {
        [$token, $siteId, $unitId, $periodId, $dueItemId] = $this->prepareDueDebtScenario();
        $payload = [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'amount' => 100,
            'currency' => 'TRY',
            'method' => 'cash',
        ];
        $first = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', $payload);
        $first->assertStatus(200);
        $one = json_decode($first->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $second = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', $payload);
        $second->assertStatus(200);
        $two = json_decode($second->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertNotSame($one['payment_no'], $two['payment_no']);
    }

    public function testIdempotencyKeyAyniysaAyniPaymentDoner(): void
    {
        [$token, $siteId, $unitId] = $this->prepareDueDebtScenario();
        $payload = [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'idempotency_key' => 'idem-123',
            'amount' => 50,
            'currency' => 'TRY',
            'method' => 'cash',
        ];
        $a = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', $payload);
        $a->assertStatus(200);
        $pa = json_decode($a->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $b = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', $payload);
        $b->assertStatus(200);
        $pb = json_decode($b->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame((int) $pa['id'], (int) $pb['id']);
    }

    public function testPaymentAllocationOldestDueDateVeStatusHesabiCalisir(): void
    {
        [$token, $siteId, $unitId] = $this->prepareDueDebtScenario(2);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'amount' => 150,
            'currency' => 'TRY',
            'method' => 'cash',
        ]);
        $res->assertStatus(200);

        $db = Database::connect();
        $items = $db->table('due_items')->where('unit_id', $unitId)->where('deleted_at', null)->orderBy('due_date', 'ASC')->get()->getResultArray();
        $this->assertSame('paid', $items[0]['status']);
        $this->assertSame('partial', $items[1]['status']);
    }

    public function testAmountValidationVeCompletedPaymentCancelEngeli(): void
    {
        [$token, $siteId, $unitId] = $this->prepareDueDebtScenario();
        $bad = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'amount' => 0,
            'currency' => 'TRY',
            'method' => 'cash',
        ]);
        $bad->assertStatus(422);

        $ok = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'amount' => 50,
            'currency' => 'TRY',
            'method' => 'cash',
        ]);
        $ok->assertStatus(200);
        $paymentId = (int) json_decode($ok->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/payments/' . $paymentId . '/cancel')->assertStatus(409);
    }

    public function testFazlaOdemeAllocatedAmountToplamBorcKadarKalir(): void
    {
        [$token, $siteId, $unitId] = $this->prepareDueDebtScenario();
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'amount' => 999,
            'currency' => 'TRY',
            'method' => 'cash',
        ]);
        $res->assertStatus(200);
        $payment = json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('completed', $payment['status']);
        $this->assertSame('100.00', number_format((float) $payment['allocated_amount'], 2, '.', ''));
    }

    public function testLockedPeriodVeCancelledDueItemAllocationDisiKalir(): void
    {
        [$token, $siteId, $unitId, $periodId, $dueItemId] = $this->prepareDueDebtScenario();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/due-items/' . $dueItemId . '/cancel')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/due-periods/' . $periodId . '/lock')->assertStatus(200);

        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/payments/manual', [
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'amount' => 100,
            'currency' => 'TRY',
            'method' => 'cash',
        ]);
        $res->assertStatus(200);
        $payment = json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('0.00', number_format((float) $payment['allocated_amount'], 2, '.', ''));
    }

    /**
     * @return array{0:string,1:int,2:int,3:int,4:int}
     */
    private function prepareDueDebtScenario(int $itemCount = 1): array
    {
        [$email] = $this->createUserWithRole('pay.user' . $itemCount . '@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', [
            'name' => 'Pay Site',
            'code' => 'PAY' . random_int(100, 999),
        ]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', [
            'site_id' => $siteId,
            'name' => 'A',
            'code' => 'A',
        ]);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'number' => 1,
        ]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => $floorId,
            'unit_no' => '1',
        ]);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $period = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-periods/', [
            'site_id' => $siteId,
            'period_key' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'due_date' => '2026-11-10',
            'status' => 'open',
        ]);
        $periodId = (int) json_decode($period->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $def = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-definitions/', [
            'site_id' => $siteId,
            'name' => 'Aidat',
            'calculation_type' => 'fixed',
            'amount' => 100,
            'currency' => 'TRY',
        ]);
        $defId = (int) json_decode($def->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
            'due_definition_id' => $defId,
            'due_period_id' => $periodId,
        ])->assertStatus(200);

        if ($itemCount > 1) {
            $def2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-definitions/', [
                'site_id' => $siteId,
                'name' => 'Ikinci Aidat',
                'code' => 'AID' . random_int(1000, 9999),
                'calculation_type' => 'fixed',
                'amount' => 100,
                'currency' => 'TRY',
            ]);
            $def2->assertStatus(200);
            $def2Id = (int) json_decode($def2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
            $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/due-batches/', [
                'due_definition_id' => $def2Id,
                'due_period_id' => $periodId,
            ])->assertStatus(200);

            Database::connect()->table('due_items')
                ->where('unit_id', $unitId)
                ->where('due_definition_id', $def2Id)
                ->where('due_period_id', $periodId)
                ->update([
                    'due_date' => '2026-11-20',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        $dueItem = Database::connect()->table('due_items')->where('unit_id', $unitId)->orderBy('due_date', 'ASC')->get()->getRowArray();
        return [$token, $siteId, $unitId, $periodId, (int) $dueItem['id']];
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
            'name' => 'Payment Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Payment',
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
