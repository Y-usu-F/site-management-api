<?php
namespace Tests\Feature\Operation;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
final class StaffManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;


    public function testStaffCrudShiftTaskFlowCalisir(): void
    {
        [$token, $siteId, $blockId] = $this->bootstrapGraph('staff1@example.com');
        $profile = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-profiles/', ['first_name' => 'Ali', 'last_name' => 'Yilmaz', 'staff_type' => 'security', 'status' => 'active']);
        $profile->assertStatus(200);
        $staffId = (int) json_decode($profile->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/staff-profiles/' . $staffId, ['phone' => '5551112233'])->assertStatus(200);

        $assignment = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-assignments/', ['staff_profile_id' => $staffId, 'site_id' => $siteId, 'block_id' => $blockId, 'start_date' => date('Y-m-d'), 'status' => 'active']);
        $assignment->assertStatus(200);
        $assignmentId = (int) json_decode($assignment->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-assignments/', ['staff_profile_id' => $staffId, 'site_id' => $siteId, 'block_id' => $blockId, 'start_date' => date('Y-m-d'), 'status' => 'active'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete('/api/v1/staff-assignments/' . $assignmentId)->assertStatus(200);

        $startAt = date('Y-m-d 08:00:00');
        $endAt = date('Y-m-d 16:00:00');
        $shift = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-shifts/', ['staff_profile_id' => $staffId, 'site_id' => $siteId, 'shift_date' => date('Y-m-d'), 'start_at' => $startAt, 'end_at' => $endAt, 'status' => 'planned']);
        $shift->assertStatus(200);
        $shiftId = (int) json_decode($shift->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/staff-shifts/' . $shiftId . '/start')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/staff-shifts/' . $shiftId . '/complete')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/staff-shifts/' . $shiftId, ['notes' => 'x'])->assertStatus(409);

        $task = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-tasks/', ['site_id' => $siteId, 'title' => 'Kontrol turu', 'priority' => 'normal']);
        $task->assertStatus(200);
        $taskId = (int) json_decode($task->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/staff-tasks/' . $taskId . '/start')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-tasks/' . $taskId . '/assign', ['staff_profile_id' => $staffId])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/staff-tasks/' . $taskId . '/start')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/staff-tasks/' . $taskId . '/complete', ['proof_note' => 'Tamamlandi'])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/staff-tasks/' . $taskId . '/cancel')->assertStatus(409);
    }

    public function testCrossTenantKurallariCalisir(): void
    {
        [$tokenA, $siteIdA] = $this->bootstrapGraph('staff2@example.com');
        [$tokenB] = $this->bootstrapGraph('staff3@example.com');
        $profileA = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-profiles/', ['first_name' => 'Ayse', 'last_name' => 'Kara', 'staff_type' => 'cleaning']);
        $profileIdA = (int) json_decode($profileA->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->get('/api/v1/staff-profiles/' . $profileIdA)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])->withBodyFormat('json')->post('/api/v1/staff-tasks/', ['site_id' => $siteIdA, 'title' => 'xx'])->assertStatus(403);
    }

    public function testValidationRaceVeAuditKurallariCalisir(): void
    {
        [$tokenA, $siteIdA, $blockIdA] = $this->bootstrapGraph('staff4@example.com');
        [$emailB, $userIdB] = $this->createUserWithRole('staff5@example.com', 'Password123!');
        $this->login($emailB, 'Password123!');

        // user_id tenant consistency
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-profiles/', [
            'user_id' => $userIdB, 'first_name' => 'Cross', 'last_name' => 'Tenant', 'staff_type' => 'technical',
        ])->assertStatus(403);

        $profile = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-profiles/', [
            'first_name' => 'Mehmet', 'last_name' => 'Demir', 'staff_type' => 'technical', 'status' => 'active',
        ]);
        $staffId = (int) json_decode($profile->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        // assignment end_date validation
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-assignments/', [
            'staff_profile_id' => $staffId, 'site_id' => $siteIdA, 'block_id' => $blockIdA, 'start_date' => '2026-05-10', 'end_date' => '2026-05-01', 'status' => 'active',
        ])->assertStatus(409);

        $assignment = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-assignments/', [
            'staff_profile_id' => $staffId, 'site_id' => $siteIdA, 'block_id' => $blockIdA, 'start_date' => '2026-05-01', 'end_date' => '2026-05-10', 'status' => 'active',
        ]);
        $assignmentId = (int) json_decode($assignment->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->put('/api/v1/staff-assignments/' . $assignmentId, [
            'start_date' => '2026-05-02', 'end_date' => '2026-05-09',
        ])->assertStatus(200);

        // shift timeline + overlap
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-shifts/', [
            'staff_profile_id' => $staffId, 'site_id' => $siteIdA, 'shift_date' => '2026-05-01', 'start_at' => '2026-05-02 10:00:00', 'end_at' => '2026-05-02 09:00:00',
        ])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-shifts/', [
            'staff_profile_id' => $staffId, 'site_id' => $siteIdA, 'shift_date' => '2026-05-01', 'start_at' => '2026-05-02 08:00:00', 'end_at' => '2026-05-02 16:00:00',
        ])->assertStatus(409);

        $shift = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-shifts/', [
            'staff_profile_id' => $staffId, 'site_id' => $siteIdA, 'shift_date' => '2026-05-02', 'start_at' => '2026-05-02 08:00:00', 'end_at' => '2026-05-02 16:00:00', 'status' => 'planned',
        ]);
        $shiftId = (int) json_decode($shift->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-shifts/', [
            'staff_profile_id' => $staffId, 'site_id' => $siteIdA, 'shift_date' => '2026-05-02', 'start_at' => '2026-05-02 12:00:00', 'end_at' => '2026-05-02 20:00:00', 'status' => 'planned',
        ])->assertStatus(409);

        // task assigned requires staff and completed update blocked
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-tasks/', [
            'site_id' => $siteIdA, 'title' => 'Atama test', 'status' => 'assigned',
        ])->assertStatus(409);
        $task = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-tasks/', [
            'site_id' => $siteIdA, 'title' => 'Audit task',
        ]);
        $taskId = (int) json_decode($task->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->post('/api/v1/staff-tasks/' . $taskId . '/assign', ['staff_profile_id' => $staffId])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->post('/api/v1/staff-tasks/' . $taskId . '/start')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->post('/api/v1/staff-tasks/' . $taskId . '/complete')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->withBodyFormat('json')->put('/api/v1/staff-tasks/' . $taskId, ['title' => 'degisemez'])->assertStatus(409);

        // audit old/new values
        $db = Database::connect();
        $row = $db->table('audit_logs')->where('event', 'operation.staff_assignment.update.success')->where('entity_id', (string) $assignmentId)->orderBy('id', 'DESC')->get(1)->getRowArray();
        $this->assertNotNull($row);
        $old = json_decode((string) ($row['old_values'] ?? '{}'), true);
        $new = json_decode((string) ($row['new_values'] ?? '{}'), true);
        $this->assertSame('2026-05-01', $old['start_date'] ?? null);
        $this->assertSame('2026-05-02', $new['start_date'] ?? null);

        // keep variable used for static analysis consistency
        $this->assertGreaterThan(0, $shiftId);
    }

    /** @return array{0:string,1:int,2:int} */
    private function bootstrapGraph(string $email): array
    {
        [$emailCreated] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Staff Site', 'code' => 'STF' . random_int(10, 99)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        return [$token, $siteId, $blockId];
    }

    /** @return array{0:string,1:int} */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(), 'name' => 'Staff Co ' . bin2hex(random_bytes(2)), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $companyId = (int) $db->insertID();
        $db->table('users')->insert(['company_id' => $companyId, 'public_id' => $this->uuid(), 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'first_name' => 'Staff', 'last_name' => 'User', 'status' => 'active', 'is_active' => 1, 'failed_login_count' => 0, 'locked_until' => null, 'created_at' => $now, 'updated_at' => $now]);
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
