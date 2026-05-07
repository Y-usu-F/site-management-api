<?php

namespace Tests\Feature\Analytics;

use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\FeatureDatabaseTestCase;

final class DashboardAnalyticsTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    public function testDashboardAnalyticsTenantScopeVeSoftDeleteFiltreleriCalisir(): void
    {
        [$ownerEmail, $ownerUserId, $ownerCompanyId] = $this->createUserWithRole('analytics.owner@example.com', 'Password123!');
        [$otherEmail, $otherUserId, $otherCompanyId] = $this->createUserWithRole('analytics.other@example.com', 'Password123!');
        $ownerToken = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $otherToken = (string) $this->login($otherEmail, 'Password123!')['data']['access_token'];

        $this->seedAnalyticsDataForCompany($ownerCompanyId, $ownerUserId, [
            'due_amount' => 1000.00,
            'due_remaining' => 250.00,
            'due_status' => 'partial',
            'payment_amount' => 750.00,
            'payment_status' => 'completed',
            'service_request_status' => 'open',
            'work_order_status' => 'in_progress',
            'reservation_status' => 'approved',
            'soft_delete' => false,
        ]);

        // Ayni tenant icinde soft-deleted kayitlar toplamlara dahil edilmemeli.
        $this->seedAnalyticsDataForCompany($ownerCompanyId, $ownerUserId, [
            'due_amount' => 999.00,
            'due_remaining' => 999.00,
            'due_status' => 'unpaid',
            'payment_amount' => 999.00,
            'payment_status' => 'completed',
            'service_request_status' => 'open',
            'work_order_status' => 'in_progress',
            'reservation_status' => 'approved',
            'soft_delete' => true,
        ]);

        // Diger tenant verisi sizmamalı.
        $this->seedAnalyticsDataForCompany($otherCompanyId, $otherUserId, [
            'due_amount' => 5000.00,
            'due_remaining' => 4000.00,
            'due_status' => 'unpaid',
            'payment_amount' => 1000.00,
            'payment_status' => 'completed',
            'service_request_status' => 'open',
            'work_order_status' => 'in_progress',
            'reservation_status' => 'approved',
            'soft_delete' => false,
        ]);

        $ownerResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $ownerToken])->get('/api/v1/analytics/dashboard');
        $ownerResponse->assertStatus(200);
        $ownerPayload = json_decode($ownerResponse->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];

        $this->assertSame(1000.0, (float) $ownerPayload['finance']['due_total']);
        $this->assertSame(750.0, (float) $ownerPayload['finance']['paid_total']);
        $this->assertSame(250.0, (float) $ownerPayload['finance']['unpaid_total']);
        $this->assertSame(1, (int) $ownerPayload['finance']['payment_count']);
        $this->assertSame(1, (int) $ownerPayload['operations']['open_service_requests']);
        $this->assertSame(1, (int) $ownerPayload['operations']['active_work_orders']);
        $this->assertSame(1, (int) $ownerPayload['operations']['upcoming_reservations']);
        $this->assertSame(1, (int) $ownerPayload['residents']['resident_count']);
        $this->assertSame(1, (int) $ownerPayload['residents']['active_occupancy_count']);
        $this->assertSame(1, (int) $ownerPayload['residents']['unit_count']);
        $this->assertCount(30, $ownerPayload['trends']['payments_last_30_days']);
        $this->assertCount(30, $ownerPayload['trends']['service_requests_last_30_days']);
        $zeroPaymentDays = array_filter(
            $ownerPayload['trends']['payments_last_30_days'],
            static fn (array $row): bool => (float) ($row['total'] ?? 0) === 0.0
        );
        $this->assertNotEmpty($zeroPaymentDays);
        $this->assertSame(1, (int) ($ownerPayload['distributions']['service_request_statuses'][0]['count'] ?? 0));
        $this->assertSame('open', (string) ($ownerPayload['distributions']['service_request_statuses'][0]['status'] ?? ''));
        $this->assertSame(1, (int) ($ownerPayload['distributions']['work_order_statuses'][0]['count'] ?? 0));
        $this->assertSame('in_progress', (string) ($ownerPayload['distributions']['work_order_statuses'][0]['status'] ?? ''));

        $otherResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $otherToken])->get('/api/v1/analytics/dashboard');
        $otherResponse->assertStatus(200);
        $otherPayload = json_decode($otherResponse->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];

        $this->assertSame(5000.0, (float) $otherPayload['finance']['due_total']);
        $this->assertSame(1000.0, (float) $otherPayload['finance']['paid_total']);
        $this->assertSame(4000.0, (float) $otherPayload['finance']['unpaid_total']);
        $this->assertSame(1, (int) $otherPayload['finance']['payment_count']);
        $this->assertCount(30, $otherPayload['trends']['payments_last_30_days']);
        $nonZeroOtherPaymentDays = array_values(array_filter(
            $otherPayload['trends']['payments_last_30_days'],
            static fn (array $row): bool => (float) ($row['total'] ?? 0) > 0
        ));
        $this->assertCount(1, $nonZeroOtherPaymentDays);
        $this->assertSame(1000.0, (float) $nonZeroOtherPaymentDays[0]['total']);
    }

    public function testDashboardAnalyticsRangeParametresiGunSayisiniBelirler(): void
    {
        [$email, $userId, $companyId] = $this->createUserWithRole('analytics.range@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $this->seedAnalyticsDataForCompany($companyId, $userId, [
            'due_amount' => 100.00,
            'due_remaining' => 20.00,
            'due_status' => 'partial',
            'payment_amount' => 80.00,
            'payment_status' => 'completed',
            'service_request_status' => 'open',
            'work_order_status' => 'in_progress',
            'reservation_status' => 'approved',
            'soft_delete' => false,
        ]);

        $defaultResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/analytics/dashboard');
        $defaultResponse->assertStatus(200);
        $defaultPayload = json_decode($defaultResponse->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertCount(30, $defaultPayload['trends']['payments_last_30_days']);

        $range7 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/analytics/dashboard?range=7d');
        $range7->assertStatus(200);
        $payload7 = json_decode($range7->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertCount(7, $payload7['trends']['payments_last_30_days']);
        $this->assertCount(7, $payload7['trends']['service_requests_last_30_days']);

        $range90 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/analytics/dashboard?range=90d');
        $range90->assertStatus(200);
        $payload90 = json_decode($range90->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertCount(90, $payload90['trends']['payments_last_30_days']);
        $this->assertCount(90, $payload90['trends']['service_requests_last_30_days']);
    }

    public function testDashboardAnalyticsInvalidRangeValidationHatasiDoner(): void
    {
        [$email] = $this->createUserWithRole('analytics.range.invalid@example.com', 'Password123!');
        $token = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/analytics/dashboard?range=15d');
        $response->assertStatus(422);
        $payload = json_decode($response->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('VALIDATION_ERROR', (string) ($payload['errors']['error_code'] ?? ''));
    }

    /**
     * @param array{
     *   due_amount:float,
     *   due_remaining:float,
     *   due_status:string,
     *   payment_amount:float,
     *   payment_status:string,
     *   service_request_status:string,
     *   work_order_status:string,
     *   reservation_status:string,
     *   soft_delete:bool
     * } $metrics
     */
    private function seedAnalyticsDataForCompany(int $companyId, int $userId, array $metrics): void
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $deletedAt = $metrics['soft_delete'] ? $now : null;

        $db->table('sites')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'name' => 'Analytics Site ' . bin2hex(random_bytes(2)),
            'code' => 'AS' . random_int(100, 999),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $siteId = (int) $db->insertID();

        $db->table('blocks')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'name' => 'Analytics Block',
            'code' => 'AB' . random_int(100, 999),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $blockId = (int) $db->insertID();

        $db->table('floors')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'number' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $floorId = (int) $db->insertID();

        $db->table('units')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => $floorId,
            'unit_no' => 'U' . random_int(100, 999),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);
        $unitId = (int) $db->insertID();

        $db->table('resident_profiles')->insert([
            'company_id' => $companyId,
            'first_name' => 'Analytics',
            'last_name' => 'Resident',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);
        $residentId = (int) $db->insertID();

        $db->table('unit_occupancies')->insert([
            'company_id' => $companyId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'owner',
            'start_date' => '2026-01-01',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);

        $db->table('due_definitions')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'name' => 'Aidat',
            'code' => 'DUE' . random_int(100, 999),
            'calculation_type' => 'fixed',
            'amount' => $metrics['due_amount'],
            'currency' => 'TRY',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $definitionId = (int) $db->insertID();

        $db->table('due_periods')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'period_key' => '2026-05',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'due_date' => '2026-05-15',
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $periodId = (int) $db->insertID();

        $db->table('due_items')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => $floorId,
            'unit_id' => $unitId,
            'due_definition_id' => $definitionId,
            'due_period_id' => $periodId,
            'due_batch_id' => null,
            'description' => 'Aylik aidat',
            'amount' => $metrics['due_amount'],
            'paid_amount' => round($metrics['due_amount'] - $metrics['due_remaining'], 2),
            'remaining_amount' => $metrics['due_remaining'],
            'currency' => 'TRY',
            'due_date' => '2026-05-15',
            'status' => $metrics['due_status'],
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);

        $db->table('payments')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'payment_no' => 'PAY-' . random_int(10000, 99999),
            'provider' => 'manual',
            'provider_reference' => null,
            'idempotency_key' => null,
            'amount' => $metrics['payment_amount'],
            'allocated_amount' => $metrics['payment_amount'],
            'currency' => 'TRY',
            'payment_date' => $now,
            'status' => $metrics['payment_status'],
            'method' => 'cash',
            'description' => 'Manual payment',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);

        $db->table('request_categories')->insert([
            'company_id' => $companyId,
            'name' => 'Teknik',
            'code' => 'TEK' . random_int(10, 99),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $categoryId = (int) $db->insertID();

        $db->table('service_requests')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'block_id' => $blockId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'category_id' => $categoryId,
            'request_no' => 'SR-' . random_int(10000, 99999),
            'title' => 'Ariza',
            'description' => 'Test ariza',
            'priority' => 'normal',
            'status' => $metrics['service_request_status'],
            'source' => 'panel',
            'assigned_to_user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);
        $serviceRequestId = (int) $db->insertID();

        $db->table('work_orders')->insert([
            'company_id' => $companyId,
            'service_request_id' => $serviceRequestId,
            'assigned_to_user_id' => $userId,
            'vendor_name' => null,
            'status' => $metrics['work_order_status'],
            'planned_start_at' => $now,
            'planned_end_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);

        $db->table('common_areas')->insert([
            'company_id' => $companyId,
            'site_id' => $siteId,
            'name' => 'Toplanti odasi',
            'code' => 'CA' . random_int(100, 999),
            'status' => 'active',
            'requires_approval' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        $commonAreaId = (int) $db->insertID();

        $db->table('common_area_reservations')->insert([
            'company_id' => $companyId,
            'common_area_id' => $commonAreaId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'reservation_no' => 'RSV-' . random_int(10000, 99999),
            'start_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'end_at' => date('Y-m-d H:i:s', strtotime('+1 day +1 hour')),
            'participant_count' => 3,
            'status' => $metrics['reservation_status'],
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $deletedAt,
        ]);
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
            'name' => 'Analytics Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Analytics',
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

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function login(string $email, string $password): array
    {
        $response = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $response->assertStatus(200);
        return json_decode($response->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }
}
