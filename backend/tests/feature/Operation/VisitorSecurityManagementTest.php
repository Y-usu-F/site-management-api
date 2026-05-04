<?php

namespace Tests\Feature\Operation;

use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\FeatureDatabaseTestCase;

final class VisitorSecurityManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;
    public function testInviteCheckinCheckoutVeBlacklistCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,$residentId]=$this->bootstrapGraph();
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/vehicle-access-lists/',['site_id'=>$siteId,'plate_number'=>'34 abc 123','list_type'=>'blacklist','status'=>'active'])->assertStatus(200);
        $invite=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-invites/',['site_id'=>$siteId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'visitor_name'=>'Misafir A','vehicle_plate'=>'34 abc 123','valid_from'=>date('Y-m-d H:i:s',strtotime('-1 hour')),'valid_until'=>date('Y-m-d H:i:s',strtotime('+2 hour')),'max_uses'=>1]);
        $invite->assertStatus(200); $inviteId=(int)json_decode($invite->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-entries/check-in',['entry_type'=>'invite','visitor_invite_id'=>$inviteId])->assertStatus(409);
        // manual with non-black plate
        $manual=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-entries/check-in',['entry_type'=>'manual','site_id'=>$siteId,'unit_id'=>$unitId,'visitor_name'=>'Manuel Kisi','vehicle_plate'=>'06 xyz 987']);
        $manual->assertStatus(200); $entryId=(int)json_decode($manual->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-entries/check-out',['entry_id'=>$entryId])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-entries/check-out',['entry_id'=>$entryId])->assertStatus(409);
    }
    public function testInviteUsageLimitVeCancelKurallariCalisir(): void
    {
        [$token,$siteId,, $unitId,$residentId]=$this->bootstrapGraph('vs4@example.com');
        $invite=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-invites/',['site_id'=>$siteId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'visitor_name'=>'Misafir B','valid_from'=>date('Y-m-d H:i:s',strtotime('-10 minute')),'valid_until'=>date('Y-m-d H:i:s',strtotime('+1 hour')),'max_uses'=>1]);
        $invite->assertStatus(200); $inviteId=(int)json_decode($invite->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-entries/check-in',['entry_type'=>'invite','visitor_invite_id'=>$inviteId])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/visitor-entries/check-in',['entry_type'=>'invite','visitor_invite_id'=>$inviteId])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/visitor-invites/'.$inviteId.'/cancel')->assertStatus(409);
    }
    public function testIncidentStateMachineVeCrossTenantCalisir(): void
    {
        [$token,$siteId]=$this->bootstrapGraph('vs2@example.com');
        $inc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/security-incidents/',['site_id'=>$siteId,'title'=>'Kapi acik kaldi']);
        $inc->assertStatus(200); $id=(int)json_decode($inc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/security-incidents/'.$id.'/resolve')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/security-incidents/'.$id.'/close')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/security-incidents/'.$id,['title'=>'xx'])->assertStatus(409);
        [$token2]=$this->bootstrapGraph('vs3@example.com');
        $this->withHeaders(['Authorization'=>'Bearer '.$token2])->get('/api/v1/security-incidents/'.$id)->assertStatus(403);
    }
    /** @return array{0:string,1:int,2:int,3:int,4:int} */
    private function bootstrapGraph(string $email='vs.user@example.com'): array
    {
        [$emailCreated]=$this->createUserWithRole($email,'Password123!'); $token=(string)$this->login($emailCreated,'Password123!')['data']['access_token'];
        $site=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/sites/',['name'=>'VS Site','code'=>'VS'.random_int(10,99)]); $siteId=(int)json_decode($site->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $block=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/blocks/',['site_id'=>$siteId,'name'=>'B1','code'=>'B1']); $blockId=(int)json_decode($block->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $floor=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/floors/',['site_id'=>$siteId,'block_id'=>$blockId,'number'=>1]); $floorId=(int)json_decode($floor->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $unit=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/units/',['site_id'=>$siteId,'block_id'=>$blockId,'floor_id'=>$floorId,'unit_no'=>'11']); $unitId=(int)json_decode($unit->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $resident=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/residents/',['first_name'=>'Ali','last_name'=>'Veli']); $residentId=(int)json_decode($resident->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/unit-occupancies/',['unit_id'=>$unitId,'resident_profile_id'=>$residentId,'relationship_type'=>'owner','start_date'=>'2026-01-01','status'=>'active'])->assertStatus(200);
        return [$token,$siteId,$blockId,$unitId,$residentId];
    }
    /** @return array{0:string,1:int} */
    private function createUserWithRole(string $email,string $password): array
    {
        $db=Database::connect(); $now=date('Y-m-d H:i:s'); $db->table('companies')->insert(['public_id'=>$this->uuid(),'name'=>'VS Co '.bin2hex(random_bytes(2)),'status'=>'active','created_at'=>$now,'updated_at'=>$now]); $companyId=(int)$db->insertID();
        $db->table('users')->insert(['company_id'=>$companyId,'public_id'=>$this->uuid(),'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'first_name'=>'VS','last_name'=>'User','status'=>'active','is_active'=>1,'failed_login_count'=>0,'locked_until'=>null,'created_at'=>$now,'updated_at'=>$now]); $userId=(int)$db->insertID();
        $role=$db->table('roles')->where('company_id',null)->where('code','company_admin')->get()->getRowArray(); $db->table('user_roles')->insert(['company_id'=>$companyId,'user_id'=>$userId,'role_id'=>(int)($role['id']??0),'created_at'=>$now,'updated_at'=>$now]); return [$email,$userId];
    }
    /** @return array<string,mixed> */
    private function login(string $email,string $password): array { $r=$this->withBodyFormat('json')->post('/api/v1/auth/login',['email'=>$email,'password'=>$password]); $r->assertStatus(200); return json_decode($r->getJSON(),true,512,JSON_THROW_ON_ERROR); }
    private function uuid(): string { $b=random_bytes(16); $b[6]=chr((ord($b[6])&0x0f)|0x40); $b[8]=chr((ord($b[8])&0x3f)|0x80); $h=bin2hex($b); return sprintf('%s-%s-%s-%s-%s',substr($h,0,8),substr($h,8,4),substr($h,12,4),substr($h,16,4),substr($h,20,12)); }
}
