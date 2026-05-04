<?php

namespace Tests\Feature\Operation;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class CommonAreaReservationTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;


    public function testCommonAreaCrudVeReservationStateMachineCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph();
        $areaRes = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-areas/', [
            'site_id' => $siteId, 'name' => 'Havuz', 'code' => 'POOL', 'capacity' => 5, 'requires_approval' => 1,
        ]);
        $areaRes->assertStatus(200);
        $areaId = (int) json_decode($areaRes->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/common-areas/' . $areaId)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/common-areas/' . $areaId, ['status' => 'active'])->assertStatus(200);

        $start = date('Y-m-d H:i:s', strtotime('+2 day 10:00:00'));
        $end = date('Y-m-d H:i:s', strtotime('+2 day 11:00:00'));
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId, 'start_at' => $start, 'end_at' => $end, 'participant_count' => 2,
        ]);
        $create->assertStatus(200);
        $created = json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('pending', $created['status']);

        $id = (int) $created['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/common-area-reservations/' . $id . '/approve')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/common-area-reservations/' . $id . '/complete')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/common-area-reservations/' . $id, ['notes' => 'x'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete('/api/v1/common-areas/' . $areaId)->assertStatus(200);
    }

    public function testApprovalFalseApprovedBaslarVeCakismaKuraliCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('ca2@example.com');
        $area = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-areas/', [
            'site_id' => $siteId, 'name' => 'Toplanti', 'requires_approval' => false, 'capacity' => 3,
        ]);
        $areaId = (int) json_decode($area->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $start = date('Y-m-d H:i:s', strtotime('+3 day 13:00:00'));
        $end = date('Y-m-d H:i:s', strtotime('+3 day 14:00:00'));
        $r1 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId, 'start_at' => $start, 'end_at' => $end, 'participant_count' => 2,
        ]);
        $r1->assertStatus(200);
        $d1 = json_decode($r1->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('approved', $d1['status']);
        $this->assertNotEmpty($d1['approved_at']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId, 'start_at' => date('Y-m-d H:i:s', strtotime('+3 day 13:30:00')), 'end_at' => date('Y-m-d H:i:s', strtotime('+3 day 14:30:00')),
        ])->assertStatus(409);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/' . (int) $d1['id'] . '/cancel', ['cancelled_reason' => 'iptal'])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId, 'start_at' => date('Y-m-d H:i:s', strtotime('+3 day 13:30:00')), 'end_at' => date('Y-m-d H:i:s', strtotime('+3 day 14:30:00')),
        ])->assertStatus(200);
    }

    public function testValidationVeCapacityVeSiteConsistencyKurallariCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('ca3@example.com');
        $areaRes = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-areas/', [
            'site_id' => $siteId, 'name' => 'Spor', 'capacity' => 2,
        ]);
        $areaId = (int) json_decode($areaRes->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        // start >= end
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId,
            'start_at' => date(DATE_ATOM, strtotime('+2 day 10:00:00')), 'end_at' => date(DATE_ATOM, strtotime('+2 day 10:00:00')),
        ])->assertStatus(409);

        // past date
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId,
            'start_at' => date(DATE_ATOM, strtotime('-1 day')), 'end_at' => date(DATE_ATOM, strtotime('+1 day')),
        ])->assertStatus(409);

        // capacity overflow
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId,
            'start_at' => date('Y-m-d H:i:s', strtotime('+4 day 10:00:00')), 'end_at' => date('Y-m-d H:i:s', strtotime('+4 day 11:00:00')),
            'participant_count' => 3,
        ])->assertStatus(409);

        // site mismatch
        $site2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'CA Site 2', 'code' => 'CA2' . random_int(10, 99)]);
        $site2Id = (int) json_decode($site2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $site2Id, 'name' => 'B2', 'code' => 'B2']);
        $block2Id = (int) json_decode($block2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $site2Id, 'block_id' => $block2Id, 'number' => 1]);
        $floor2Id = (int) json_decode($floor2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $site2Id, 'block_id' => $block2Id, 'floor_id' => $floor2Id, 'unit_no' => '99']);
        $unit2Id = (int) json_decode($unit2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unit2Id, 'resident_profile_id' => $residentId,
            'start_at' => date('Y-m-d H:i:s', strtotime('+5 day 10:00:00')), 'end_at' => date('Y-m-d H:i:s', strtotime('+5 day 11:00:00')),
        ])->assertStatus(409);
    }

    public function testOccupancyCrossTenantVeIllegalTransitionsCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('ca5@example.com');
        $area = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-areas/', [
            'site_id' => $siteId, 'name' => 'Sinema', 'requires_approval' => 1,
        ]);
        $areaId = (int) json_decode($area->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        // occupancy missing
        $resident2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/residents/', ['first_name' => 'No', 'last_name' => 'Occ']);
        $resident2Id = (int) json_decode($resident2->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $resident2Id,
            'start_at' => date('Y-m-d H:i:s', strtotime('+6 day 10:00:00')), 'end_at' => date('Y-m-d H:i:s', strtotime('+6 day 11:00:00')),
        ])->assertStatus(409);

        // create pending and illegal transition checks
        $ok = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId, 'unit_id' => $unitId, 'resident_profile_id' => $residentId,
            'start_at' => date('Y-m-d H:i:s', strtotime('+7 day 10:00:00')), 'end_at' => date('Y-m-d H:i:s', strtotime('+7 day 11:00:00')),
        ]);
        $ok->assertStatus(200);
        $reservationId = (int) json_decode($ok->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/common-area-reservations/' . $reservationId . '/complete')->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/' . $reservationId . '/reject', ['rejected_reason' => 'uygun degil'])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/common-area-reservations/' . $reservationId . '/approve')->assertStatus(409);

        // cross-tenant 403
        [$token2] = $this->bootstrapGraph('ca6@example.com');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])->get('/api/v1/common-areas/' . $areaId)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])->get('/api/v1/common-area-reservations/' . $reservationId)->assertStatus(403);
    }

    public function testOverlapHardeningSequentialSimulationVeUpdateExcludeCalisir(): void
    {
        [$token, $siteId, $unitId, $residentId] = $this->bootstrapGraph('ca7@example.com');
        $area = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-areas/', [
            'site_id' => $siteId,
            'name' => 'Fitness',
            'requires_approval' => false,
        ]);
        $areaId = (int) json_decode($area->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $start = date('Y-m-d H:i:s', strtotime('+8 day 10:00:00'));
        $end = date('Y-m-d H:i:s', strtotime('+8 day 11:00:00'));
        $first = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'start_at' => $start,
            'end_at' => $end,
        ]);
        $first->assertStatus(200);
        $firstData = json_decode($first->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];

        // Sequential simulation of concurrent second request on same slot.
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/common-area-reservations/', [
            'common_area_id' => $areaId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'start_at' => date('Y-m-d H:i:s', strtotime('+8 day 10:15:00')),
            'end_at' => date('Y-m-d H:i:s', strtotime('+8 day 10:45:00')),
        ])->assertStatus(409);

        // Update self on same timeline should not conflict (exclude current reservation id).
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put(
            '/api/v1/common-area-reservations/' . (int) $firstData['id'],
            [
                'start_at' => $start,
                'end_at' => $end,
                'notes' => 'self-update-ok',
            ]
        )->assertStatus(200);
    }

    /**
     * @return array{0:string,1:int,2:int,3:int}
     */
    private function bootstrapGraph(string $email = 'ca.user@example.com'): array
    {
        [$emailCreated] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'CA Site', 'code' => 'CA' . random_int(10, 99)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $siteId, 'block_id' => $blockId, 'floor_id' => $floorId, 'unit_no' => '11']);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $resident = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/residents/', ['first_name' => 'Ali', 'last_name' => 'Veli']);
        $residentId = (int) json_decode($resident->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId, 'resident_profile_id' => $residentId, 'relationship_type' => 'owner', 'start_date' => '2026-01-01', 'status' => 'active',
        ])->assertStatus(200);
        return [$token, $siteId, $unitId, $residentId];
    }
    /** @return array{0:string,1:int} */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect(); $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(),'name' => 'Common Area Co ' . bin2hex(random_bytes(2)),'status' => 'active','created_at' => $now,'updated_at' => $now,]); $companyId = (int) $db->insertID();
        $db->table('users')->insert(['company_id' => $companyId,'public_id' => $this->uuid(),'email' => $email,'password_hash' => password_hash($password, PASSWORD_DEFAULT),'first_name' => 'Common','last_name' => 'Area','status' => 'active','is_active' => 1,'failed_login_count' => 0,'locked_until' => null,'created_at' => $now,'updated_at' => $now,]); $userId = (int) $db->insertID();
        $role = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        $db->table('user_roles')->insert(['company_id' => $companyId,'user_id' => $userId,'role_id' => (int) ($role['id'] ?? 0),'created_at' => $now,'updated_at' => $now,]);
        return [$email, $userId];
    }
    /** @return array<string,mixed> */ private function login(string $email, string $password): array { $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]); $result->assertStatus(200); return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR); }
    private function uuid(): string { $bytes = random_bytes(16); $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); $hex = bin2hex($bytes); return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12)); }
}
