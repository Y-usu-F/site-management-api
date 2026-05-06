<?php

namespace Tests\Feature\Resident;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class ResidentManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testResidentCrudVeAuditCalisir(): void
    {
        [$email] = $this->createUserWithRole('resident.crud@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/residents/', [
                'first_name' => 'Ali',
                'last_name' => 'Yilmaz',
                'status' => 'active',
            ]);
        $create->assertStatus(200);
        $residentId = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/residents/' . $residentId)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')
            ->put('/api/v1/residents/' . $residentId, ['first_name' => 'Veli'])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->delete('/api/v1/residents/' . $residentId)->assertStatus(200);

        $db = Database::connect();
        $audit = $db->table('audit_logs')->where('event', 'resident.profile.update.success')->countAllResults();
        $this->assertGreaterThan(0, $audit);
    }

    public function testOccupancyKurallariVeDuplicateEngeliCalisir(): void
    {
        [$email] = $this->createUserWithRole('occupancy.rules@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-1');
        $residentId = $this->createResident($access, 'Ayse', 'Demir');

        $payload = [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'owner',
            'start_date' => '2026-01-01',
            'is_primary' => true,
            'status' => 'active',
        ];

        $first = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', $payload);
        $first->assertStatus(200);

        $dup = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', $payload);
        $dup->assertStatus(409);
    }

    public function testPrimaryOwnerVePrimaryTenantTekilOlmali(): void
    {
        [$email] = $this->createUserWithRole('primary.rules@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-2');
        $residentA = $this->createResident($access, 'Owner', 'Aa');
        $residentB = $this->createResident($access, 'Owner', 'Bb');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentA,
            'relationship_type' => 'owner',
            'start_date' => '2026-01-01',
            'is_primary' => true,
            'status' => 'active',
        ])->assertStatus(200);

        $ownerDupPrimary = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentB,
            'relationship_type' => 'owner',
            'start_date' => '2026-01-05',
            'is_primary' => true,
            'status' => 'active',
        ]);
        $ownerDupPrimary->assertStatus(409);
    }

    public function testPrimaryTenantTekilOlmali(): void
    {
        [$email] = $this->createUserWithRole('primary.tenant@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-2B');
        $residentA = $this->createResident($access, 'Tenant', 'Aa');
        $residentB = $this->createResident($access, 'Tenant', 'Bb');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentA,
            'relationship_type' => 'tenant',
            'start_date' => '2026-01-01',
            'is_primary' => true,
            'status' => 'active',
        ])->assertStatus(200);

        $dupPrimary = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentB,
            'relationship_type' => 'tenant',
            'start_date' => '2026-01-03',
            'is_primary' => true,
            'status' => 'active',
        ]);
        $dupPrimary->assertStatus(409);
    }

    public function testVehiclePlateNormalizeVeDuplicateActivePlateEngellenir(): void
    {
        [$email] = $this->createUserWithRole('vehicle.plate@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-3');
        $residentId = $this->createResident($access, 'Arac', 'Sahibi');

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/resident-vehicles/', [
            'resident_profile_id' => $residentId,
            'unit_id' => $unitId,
            'plate_number' => '34 ab 1234',
            'status' => 'active',
        ]);
        $create->assertStatus(200);
        $vehicle = json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $this->assertSame('34AB1234', $vehicle['plate_number']);

        $dup = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/resident-vehicles/', [
            'resident_profile_id' => $residentId,
            'plate_number' => '34AB1234',
            'status' => 'active',
        ]);
        $dup->assertStatus(409);
    }

    public function testPrimaryContactTekillestirilir(): void
    {
        [$email] = $this->createUserWithRole('contact.primary@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $residentId = $this->createResident($access, 'Iletisim', 'Kisi');

        $first = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/resident-contacts/', [
            'resident_profile_id' => $residentId,
            'type' => 'phone',
            'value' => '5551111111',
            'is_primary' => true,
        ]);
        $first->assertStatus(200);
        $firstId = (int) json_decode($first->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $second = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/resident-contacts/', [
            'resident_profile_id' => $residentId,
            'type' => 'phone',
            'value' => '5552222222',
            'is_primary' => true,
        ]);
        $second->assertStatus(200);

        $db = Database::connect();
        $firstRow = $db->table('resident_contacts')->where('id', $firstId)->get()->getRowArray();
        $this->assertSame(0, (int) ($firstRow['is_primary'] ?? 0));
    }

    public function testResidentContactCreateVeListCompanyIdOlmadanCalisirVeSpoofedCompanyIdYokSayilir(): void
    {
        [$email, $userId] = $this->createUserWithRole('contact.tenant.context@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $residentId = $this->createResident($access, 'Context', 'Owner');
        $authCompanyId = $this->getUserCompanyId($userId);
        $spoofedCompanyId = $this->createCompany('Contact Spoof Co');

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/resident-contacts/', [
            'company_id' => $spoofedCompanyId,
            'resident_profile_id' => $residentId,
            'type' => 'phone',
            'value' => '5553334444',
            'is_primary' => true,
        ]);
        $create->assertStatus(200);
        $created = json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $contactId = (int) $created['id'];

        $list = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->get('/api/v1/resident-contacts?resident_profile_id=' . $residentId);
        $list->assertStatus(200);
        $listPayload = json_decode($list->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['items'];
        $this->assertNotEmpty($listPayload);
        $this->assertContains($contactId, array_map(static fn (array $row): int => (int) $row['id'], $listPayload));

        $row = Database::connect()->table('resident_contacts')->where('id', $contactId)->get()->getRowArray();
        $this->assertIsArray($row);
        $this->assertSame($authCompanyId, (int) ($row['company_id'] ?? 0));
    }

    public function testResidentContactCrossTenantErisimiEngellenir(): void
    {
        [$ownerEmail] = $this->createUserWithRole('contact.owner@example.com', 'Password123!');
        [$attackerEmail] = $this->createUserWithRole('contact.attacker@example.com', 'Password123!');
        $ownerAccess = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $attackerAccess = (string) $this->login($attackerEmail, 'Password123!')['data']['access_token'];

        $ownerResidentId = $this->createResident($ownerAccess, 'Contact', 'TenantA');
        $ownerContactCreate = $this->withHeaders(['Authorization' => 'Bearer ' . $ownerAccess])->withBodyFormat('json')->post('/api/v1/resident-contacts/', [
            'resident_profile_id' => $ownerResidentId,
            'type' => 'phone',
            'value' => '5551010101',
            'is_primary' => true,
        ]);
        $ownerContactCreate->assertStatus(200);
        $ownerContactId = (int) json_decode($ownerContactCreate->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $attackerCreate = $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->withBodyFormat('json')->post('/api/v1/resident-contacts/', [
            'resident_profile_id' => $ownerResidentId,
            'type' => 'phone',
            'value' => '5552020202',
        ]);
        $attackerCreate->assertStatus(403);

        $attackerList = $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->get('/api/v1/resident-contacts?resident_profile_id=' . $ownerResidentId);
        $attackerList->assertStatus(200);
        $attackerItems = json_decode($attackerList->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['items'];
        $this->assertSame([], $attackerItems);

        $attackerUpdate = $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->withBodyFormat('json')->put('/api/v1/resident-contacts/' . $ownerContactId, [
            'label' => 'hijack',
        ]);
        $attackerUpdate->assertStatus(403);

        $attackerDelete = $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->delete('/api/v1/resident-contacts/' . $ownerContactId);
        $attackerDelete->assertStatus(403);
    }

    public function testResidentVehicleCompanyIdOlmadanCalisirSpoofYokSayilirVeCrossTenantEngellenir(): void
    {
        [$ownerEmail, $ownerUserId] = $this->createUserWithRole('vehicle.owner.context@example.com', 'Password123!');
        [$attackerEmail] = $this->createUserWithRole('vehicle.attacker.context@example.com', 'Password123!');
        $ownerAccess = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $attackerAccess = (string) $this->login($attackerEmail, 'Password123!')['data']['access_token'];

        [$unitId] = $this->createUnitGraph($ownerAccess, 'R-SITE-VCTX');
        $residentId = $this->createResident($ownerAccess, 'Vehicle', 'Owner');
        $ownerCompanyId = $this->getUserCompanyId($ownerUserId);
        $spoofedCompanyId = $this->createCompany('Vehicle Spoof Co');

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $ownerAccess])->withBodyFormat('json')->post('/api/v1/resident-vehicles/', [
            'company_id' => $spoofedCompanyId,
            'resident_profile_id' => $residentId,
            'unit_id' => $unitId,
            'plate_number' => '34 xyz 123',
            'status' => 'active',
        ]);
        $create->assertStatus(200);
        $vehicle = json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $vehicleId = (int) $vehicle['id'];

        $list = $this->withHeaders(['Authorization' => 'Bearer ' . $ownerAccess])->get('/api/v1/resident-vehicles?resident_profile_id=' . $residentId);
        $list->assertStatus(200);
        $listItems = json_decode($list->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['items'];
        $this->assertContains($vehicleId, array_map(static fn (array $row): int => (int) $row['id'], $listItems));

        $row = Database::connect()->table('resident_vehicles')->where('id', $vehicleId)->get()->getRowArray();
        $this->assertIsArray($row);
        $this->assertSame($ownerCompanyId, (int) ($row['company_id'] ?? 0));

        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->get('/api/v1/resident-vehicles/' . $vehicleId)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->withBodyFormat('json')
            ->put('/api/v1/resident-vehicles/' . $vehicleId, ['color' => 'black'])->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->delete('/api/v1/resident-vehicles/' . $vehicleId)->assertStatus(403);
    }

    public function testUnitOccupancyCompanyIdOlmadanCalisirSpoofYokSayilirVeCrossTenantEngellenir(): void
    {
        [$ownerEmail, $ownerUserId] = $this->createUserWithRole('occupancy.owner.context@example.com', 'Password123!');
        [$attackerEmail] = $this->createUserWithRole('occupancy.attacker.context@example.com', 'Password123!');
        $ownerAccess = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $attackerAccess = (string) $this->login($attackerEmail, 'Password123!')['data']['access_token'];

        [$unitId] = $this->createUnitGraph($ownerAccess, 'R-SITE-OCTX');
        $residentId = $this->createResident($ownerAccess, 'Occupancy', 'Owner');
        $ownerCompanyId = $this->getUserCompanyId($ownerUserId);
        $spoofedCompanyId = $this->createCompany('Occupancy Spoof Co');

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $ownerAccess])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'company_id' => $spoofedCompanyId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'tenant',
            'start_date' => '2026-09-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        $create->assertStatus(200);
        $occupancy = json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $occupancyId = (int) $occupancy['id'];

        $list = $this->withHeaders(['Authorization' => 'Bearer ' . $ownerAccess])->get('/api/v1/unit-occupancies?unit_id=' . $unitId);
        $list->assertStatus(200);
        $listItems = json_decode($list->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['items'];
        $this->assertContains($occupancyId, array_map(static fn (array $row): int => (int) $row['id'], $listItems));

        $row = Database::connect()->table('unit_occupancies')->where('id', $occupancyId)->get()->getRowArray();
        $this->assertIsArray($row);
        $this->assertSame($ownerCompanyId, (int) ($row['company_id'] ?? 0));

        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->get('/api/v1/unit-occupancies/' . $occupancyId)->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->withBodyFormat('json')
            ->put('/api/v1/unit-occupancies/' . $occupancyId, ['status' => 'passive'])->assertStatus(403);
        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->delete('/api/v1/unit-occupancies/' . $occupancyId)->assertStatus(403);
    }

    public function testEndDateStartDatetenKucukOlamaz(): void
    {
        [$email] = $this->createUserWithRole('occupancy.date@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-4');
        $residentId = $this->createResident($access, 'Tarih', 'Kontrol');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'tenant',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-01',
            'status' => 'active',
        ]);
        $result->assertStatus(409);
    }

    public function testSoftDeletedUnitIleOccupancyAcilamaz(): void
    {
        [$email] = $this->createUserWithRole('soft.unit@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-5');
        $residentId = $this->createResident($access, 'Soft', 'Unit');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->delete('/api/v1/units/' . $unitId)->assertStatus(200);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'resident',
            'start_date' => '2026-06-01',
            'status' => 'active',
        ]);
        $result->assertStatus(404);
    }

    public function testSoftDeletedResidentIleOccupancyAcilamaz(): void
    {
        [$email] = $this->createUserWithRole('soft.resident@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-6');
        $residentId = $this->createResident($access, 'Soft', 'Resident');

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->delete('/api/v1/residents/' . $residentId)->assertStatus(200);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'resident',
            'start_date' => '2026-06-01',
            'status' => 'active',
        ]);
        $result->assertStatus(404);
    }

    public function testTransactionRaceConditionSimulasyonu(): void
    {
        [$email] = $this->createUserWithRole('race.sim@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        [$unitId] = $this->createUnitGraph($access, 'R-SITE-7');
        $residentId = $this->createResident($access, 'Race', 'Cond');

        $payload = [
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'tenant',
            'start_date' => '2026-07-01',
            'is_primary' => true,
            'status' => 'active',
        ];

        $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', $payload)->assertStatus(200);
        $second = $this->withHeaders(['Authorization' => 'Bearer ' . $access])->withBodyFormat('json')->post('/api/v1/unit-occupancies/', $payload);
        $second->assertStatus(409);
    }

    public function testCrossTenantErisim403Doner(): void
    {
        [$ownerEmail] = $this->createUserWithRole('resident.owner@example.com', 'Password123!');
        [$attackerEmail] = $this->createUserWithRole('resident.attacker@example.com', 'Password123!');
        $ownerAccess = (string) $this->login($ownerEmail, 'Password123!')['data']['access_token'];
        $attackerAccess = (string) $this->login($attackerEmail, 'Password123!')['data']['access_token'];
        $residentId = $this->createResident($ownerAccess, 'Tenant', 'Owner');

        $this->withHeaders(['Authorization' => 'Bearer ' . $attackerAccess])->get('/api/v1/residents/' . $residentId)->assertStatus(403);
    }

    public function testResidentCreateSpoofedCompanyIdYokSayilirVeAuthTenantaYazilir(): void
    {
        [$email, $userId] = $this->createUserWithRole('resident.spoofed.company@example.com', 'Password123!');
        $access = (string) $this->login($email, 'Password123!')['data']['access_token'];
        $authCompanyId = $this->getUserCompanyId($userId);
        $spoofedCompanyId = $this->createCompany('Spoofed Co');

        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $access])
            ->withBodyFormat('json')
            ->post('/api/v1/residents/', [
                'company_id' => $spoofedCompanyId,
                'first_name' => 'Spoof',
                'last_name' => 'Tenant',
                'status' => 'active',
            ]);
        $create->assertStatus(200);
        $residentId = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $row = Database::connect()->table('resident_profiles')->where('id', $residentId)->get()->getRowArray();
        $this->assertIsArray($row);
        $this->assertSame($authCompanyId, (int) ($row['company_id'] ?? 0));
    }

    /**
     * @return array{0:string,1:int}
     */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $companyId = $this->createCompany('Resident Co ' . bin2hex(random_bytes(2)));
        $db->table('users')->insert([
            'company_id' => $companyId,
            'public_id' => $this->uuid(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'Resident',
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
        $roleId = (int) ($role['id'] ?? 0);
        $db->table('user_roles')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return [$email, $userId];
    }

    private function getUserCompanyId(int $userId): int
    {
        $row = Database::connect()->table('users')->select('company_id')->where('id', $userId)->get()->getRowArray();
        return (int) ($row['company_id'] ?? 0);
    }

    private function login(string $email, string $password): array
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        $result->assertStatus(200);
        return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createResident(string $accessToken, string $firstName, string $lastName): int
    {
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])->withBodyFormat('json')->post('/api/v1/residents/', [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);
        $res->assertStatus(200);
        return (int) json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
    }

    /**
     * @return array{0:int,1:int,2:int,3:int}
     */
    private function createUnitGraph(string $accessToken, string $siteCode): array
    {
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])->withBodyFormat('json')->post('/api/v1/sites/', [
            'name' => 'Resident Site',
            'code' => $siteCode,
            'status' => 'active',
        ]);
        $site->assertStatus(200);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])->withBodyFormat('json')->post('/api/v1/blocks/', [
            'site_id' => $siteId,
            'name' => 'A',
            'code' => 'A',
            'status' => 'active',
        ]);
        $block->assertStatus(200);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])->withBodyFormat('json')->post('/api/v1/floors/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'number' => 1,
            'label' => '1',
            'status' => 'active',
        ]);
        $floor->assertStatus(200);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $accessToken])->withBodyFormat('json')->post('/api/v1/units/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'floor_id' => $floorId,
            'unit_no' => '1',
            'status' => 'active',
        ]);
        $unit->assertStatus(200);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        return [$unitId, $siteId, $blockId, $floorId];
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }

    private function createCompany(string $name): int
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert([
            'public_id' => $this->uuid(),
            'name' => $name,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $db->insertID();
    }
}
