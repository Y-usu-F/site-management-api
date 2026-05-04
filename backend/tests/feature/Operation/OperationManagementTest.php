<?php

namespace Tests\Feature\Operation;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class OperationManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;



    public function testCategoryCrudCalisir(): void
    {
        [$token] = $this->bootstrapGraph();
        $create = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/request-categories/', [
            'name' => 'Elektrik',
            'code' => 'ELEC',
        ]);
        $create->assertStatus(200);
        $id = (int) json_decode($create->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/request-categories/' . $id)->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->put('/api/v1/request-categories/' . $id, ['status' => 'passive'])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete('/api/v1/request-categories/' . $id)->assertStatus(200);
    }

    public function testServiceRequestStatusTransitionsVeWorkOrderCloseGuard(): void
    {
        [$token, $siteId, $blockId, $unitId, $residentId] = $this->bootstrapGraph();
        $req = $this->createServiceRequest($token, $siteId, $blockId, $unitId, $residentId);
        $reqId = (int) $req['id'];
        $userId = (int) $this->currentUserIdByToken($token);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/service-requests/' . $reqId . '/assign', ['assigned_to_user_id' => $userId])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/service-requests/' . $reqId . '/resolve')->assertStatus(200);

        $wo = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/work-orders/', ['service_request_id' => $reqId]);
        $wo->assertStatus(200);
        $woPayload = json_decode($wo->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/service-requests/' . $reqId . '/close')->assertStatus(409);

        $woId = (int) $woPayload['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/work-orders/' . $woId . '/cancel')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/service-requests/' . $reqId . '/close')->assertStatus(200);
    }

    public function testClosedRequestCommentEngellenirVeFileCreateDeleteCalisir(): void
    {
        [$token, $siteId, $blockId, $unitId, $residentId] = $this->bootstrapGraph();
        $req = $this->createServiceRequest($token, $siteId, $blockId, $unitId, $residentId);
        $reqId = (int) $req['id'];
        $userId = (int) $this->currentUserIdByToken($token);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/service-requests/' . $reqId . '/assign', ['assigned_to_user_id' => $userId])->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/service-requests/' . $reqId . '/resolve')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/service-requests/' . $reqId . '/close')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/service-requests/' . $reqId . '/comments', ['comment' => 'x'])->assertStatus(409);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/service-requests/' . $reqId . '/files', [
            'file_name' => 'blocked.jpg',
            'file_path' => '/tmp/blocked.jpg',
        ])->assertStatus(409);

        [$token2, $site2, $block2, $unit2, $resident2] = $this->bootstrapGraph('u2@example.com');
        $req2 = $this->createServiceRequest($token2, $site2, $block2, $unit2, $resident2);
        $req2Id = (int) $req2['id'];
        $fileRes = $this->withHeaders(['Authorization' => 'Bearer ' . $token2])->withBodyFormat('json')->post('/api/v1/service-requests/' . $req2Id . '/files', [
            'file_name' => 'foto.jpg',
            'file_path' => '/tmp/foto.jpg',
        ]);
        $fileRes->assertStatus(200);
        $fileId = (int) json_decode($fileRes->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])->delete('/api/v1/service-request-files/' . $fileId)->assertStatus(200);
    }

    public function testWorkOrderStartTekrarlandigindaConflictDoner(): void
    {
        [$token, $siteId, $blockId, $unitId, $residentId] = $this->bootstrapGraph('u3@example.com');
        $req = $this->createServiceRequest($token, $siteId, $blockId, $unitId, $residentId);
        $wo = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/work-orders/', ['service_request_id' => (int) $req['id']]);
        $wo->assertStatus(200);
        $woId = (int) json_decode($wo->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/work-orders/' . $woId . '/start')->assertStatus(200);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/v1/work-orders/' . $woId . '/start')->assertStatus(409);
    }

    /**
     * @return array{0:string,1:int,2:int,3:int,4:int}
     */
    private function bootstrapGraph(string $email = 'op.user@example.com'): array
    {
        [$emailCreated] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];

        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Ops Site', 'code' => 'OPS' . random_int(10, 99)]);
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
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'relationship_type' => 'owner',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ])->assertStatus(200);

        return [$token, $siteId, $blockId, $unitId, $residentId];
    }

    /**
     * @return array<string,mixed>
     */
    private function createServiceRequest(string $token, int $siteId, int $blockId, int $unitId, int $residentId): array
    {
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/service-requests/', [
            'site_id' => $siteId,
            'block_id' => $blockId,
            'unit_id' => $unitId,
            'resident_profile_id' => $residentId,
            'title' => 'Musluk arizasi',
            'description' => 'Su kacagi var',
            'priority' => 'high',
            'source' => 'panel',
        ]);
        $res->assertStatus(200);
        return json_decode($res->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data'];
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
            'name' => 'Operation Co ' . bin2hex(random_bytes(2)),
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
            'first_name' => 'Operation',
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

    /**
     * @return array<string,mixed>
     */
    private function login(string $email, string $password): array
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        $result->assertStatus(200);
        return json_decode($result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function currentUserIdByToken(string $token): int
    {
        $me = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/v1/auth/me');
        $me->assertStatus(200);
        return (int) json_decode($me->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
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
