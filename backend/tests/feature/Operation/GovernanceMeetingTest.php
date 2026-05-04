<?php
namespace Tests\Feature\Operation;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
final class GovernanceMeetingTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;


    public function testMeetingAgendaAttendeeDecisionFlowCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,$residentId] = $this->bootstrapGraph('gov1@example.com');
        $meeting=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/meetings/',['site_id'=>$siteId,'meeting_type'=>'general_assembly','title'=>'Genel Kurul','meeting_date'=>date('Y-m-d H:i:s')]);
        $meeting->assertStatus(200);
        $meetingId=(int)json_decode($meeting->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/meetings/'.$meetingId.'/complete')->assertStatus(409);
        $agenda1=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/meetings/'.$meetingId.'/agenda',['title'=>'Acilis']);
        $agenda1->assertStatus(200);
        $itemId=(int)json_decode($agenda1->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/meeting-agenda-items/'.$itemId,['title'=>'Acilis ve yoklama'])->assertStatus(200);
        $att=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/meetings/'.$meetingId.'/attendees',['resident_profile_id'=>$residentId,'unit_id'=>$unitId,'attendance_type'=>'owner','status'=>'invited']);
        $att->assertStatus(200);
        $attId=(int)json_decode($att->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/meeting-attendees/'.$attId.'/sign')->assertStatus(200);
        $decision=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/decision-book-entries/',['meeting_id'=>$meetingId,'decision_date'=>date('Y-m-d'),'subject'=>'Aidat karari','decision_text'=>'Oy coklugu ile kabul','vote_result'=>'majority']);
        $decision->assertStatus(200);
        $decisionId=(int)json_decode($decision->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/decision-book-entries/'.$decisionId.'/approve')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/decision-book-entries/'.$decisionId.'/approve')->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/decision-book-entries/'.$decisionId.'/lock')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/decision-book-entries/'.$decisionId,['subject'=>'xx'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/meetings/'.$meetingId.'/publish')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/meetings/'.$meetingId.'/complete')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/meetings/'.$meetingId.'/lock')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/meetings/'.$meetingId.'/agenda',['title'=>'Kilitli ekleme'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/meetings/'.$meetingId.'/attendees',['resident_profile_id'=>$residentId,'unit_id'=>$unitId,'attendance_type'=>'owner'])->assertStatus(409);
    }

    public function testCrossTenantVeDuplicateKurallariCalisir(): void
    {
        [$tokenA,$siteA,$blockA,$unitA,$residentA] = $this->bootstrapGraph('gov2@example.com');
        [$tokenB,$siteB] = $this->bootstrapGraph('gov3@example.com');
        $meeting=$this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/meetings/',['site_id'=>$siteA,'meeting_type'=>'board_meeting','title'=>'Yonetim','meeting_date'=>date('Y-m-d H:i:s')]);
        $meetingId=(int)json_decode($meeting->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->get('/api/v1/meetings/'.$meetingId)->assertStatus(403);
        $att=$this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/meetings/'.$meetingId.'/attendees',['resident_profile_id'=>$residentA,'unit_id'=>$unitA,'attendance_type'=>'owner']);
        $att->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/meetings/'.$meetingId.'/attendees',['resident_profile_id'=>$residentA,'unit_id'=>$unitA,'attendance_type'=>'owner'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/meetings/',['site_id'=>$siteB,'meeting_type'=>'board_meeting','title'=>'Cross','meeting_date'=>date('Y-m-d H:i:s')])->assertStatus(403);
    }

    /** @return array{0:string,1:int,2:int,3:int,4:int} */
    private function bootstrapGraph(string $email): array
    {
        [$emailCreated,$userId,$companyId] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Gov Site', 'code' => 'GOV' . random_int(10, 99)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $siteId, 'block_id' => $blockId, 'floor_id' => $floorId, 'unit_no' => '11']);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $db=Database::connect(); $now=date('Y-m-d H:i:s');
        $db->table('resident_profiles')->insert(['company_id'=>$companyId,'user_id'=>$userId,'first_name'=>'Res','last_name'=>'One','status'=>'active','created_at'=>$now,'updated_at'=>$now]);
        $residentId=(int)$db->insertID();
        $db->table('unit_occupancies')->insert(['company_id'=>$companyId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'relationship_type'=>'owner','start_date'=>date('Y-m-d'),'status'=>'active','created_at'=>$now,'updated_at'=>$now]);
        return [$token,$siteId,$blockId,$unitId,$residentId];
    }

    /** @return array{0:string,1:int,2:int} */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect(); $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(), 'name' => 'Gov Co ' . bin2hex(random_bytes(2)), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]); $companyId = (int) $db->insertID();
        $db->table('users')->insert(['company_id' => $companyId, 'public_id' => $this->uuid(), 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'first_name' => 'Gov', 'last_name' => 'User', 'status' => 'active', 'is_active' => 1, 'failed_login_count' => 0, 'locked_until' => null, 'created_at' => $now, 'updated_at' => $now]); $userId = (int) $db->insertID();
        $role = $db->table('roles')->where('company_id', null)->where('code', 'company_admin')->get()->getRowArray();
        $db->table('user_roles')->insert(['company_id' => $companyId, 'user_id' => $userId, 'role_id' => (int) ($role['id'] ?? 0), 'created_at' => $now, 'updated_at' => $now]);
        return [$email, $userId, $companyId];
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
        $bytes = random_bytes(16); $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}
