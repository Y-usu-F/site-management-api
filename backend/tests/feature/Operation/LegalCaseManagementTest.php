<?php
namespace Tests\Feature\Operation;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
final class LegalCaseManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;


    public function testLegalCaseDebtEventDocumentFlowCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,$residentId,$dueItemId,$documentId] = $this->bootstrapGraph('legal1@example.com');
        $case=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/',['site_id'=>$siteId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'case_type'=>'enforcement']);
        $case->assertStatus(200);
        $caseId=(int)json_decode($case->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseId.'/debts',['due_item_id'=>$dueItemId,'interest_amount'=>10])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseId.'/debts',['due_item_id'=>$dueItemId])->assertStatus(409);
        $ev=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseId.'/events',['event_type'=>'note','event_date'=>date('Y-m-d H:i:s'),'description'=>'ilk not']);
        $ev->assertStatus(200);
        $doc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseId.'/documents',['document_id'=>$documentId,'document_type'=>'notice']);
        $doc->assertStatus(200);
        $caseDocId=(int)json_decode($doc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseId.'/documents',['document_id'=>$documentId,'document_type'=>'notice'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->delete('/api/v1/legal-case-documents/'.$caseDocId)->assertStatus(200);
    }

    public function testStateMachineCrossTenantVeDebtGuardCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,$residentId,$dueItemId] = $this->bootstrapGraph('legal2@example.com');
        [$tokenB] = $this->bootstrapGraph('legal3@example.com');
        $case=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/',['site_id'=>$siteId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'case_type'=>'legal_notice']);
        $caseId=(int)json_decode($case->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->get('/api/v1/legal-cases/'.$caseId)->assertStatus(403);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/legal-cases/'.$caseId.'/send-to-lawyer')->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/legal-cases/'.$caseId,['status'=>'prepared'])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/legal-cases/'.$caseId.'/send-to-lawyer')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/legal-cases/'.$caseId.'/file')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/legal-cases/'.$caseId.'/mark-paid')->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/legal-cases/'.$caseId,['status'=>'in_progress'])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/legal-cases/'.$caseId.'/mark-paid')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/legal-cases/'.$caseId.'/close')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/legal-cases/'.$caseId,['lawyer_name'=>'x'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseId.'/debts',['due_item_id'=>$dueItemId])->assertStatus(409);
    }

    public function testUpdateStatusTransitionAndAuditCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,$residentId] = $this->bootstrapGraph('legal4@example.com');
        $case=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/legal-cases/',['site_id'=>$siteId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'case_type'=>'enforcement']);
        $caseId=(int)json_decode($case->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/legal-cases/'.$caseId,['status'=>'in_progress'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/legal-cases/'.$caseId,['status'=>'prepared'])->assertStatus(200);

        $audit=Database::connect()->table('audit_logs')->where('event','legal.legal_case.update.success')->where('entity_id',(string)$caseId)->orderBy('id','DESC')->get(1)->getRowArray();
        $this->assertIsArray($audit);
        $old=json_decode((string)($audit['old_values']??'{}'),true);
        $new=json_decode((string)($audit['new_values']??'{}'),true);
        $this->assertSame('draft',(string)($old['status']??''));
        $this->assertSame('prepared',(string)($new['status']??''));
    }

    public function testDebtEventDocumentCrossTenantVeDueSiteConsistencyCalisir(): void
    {
        [$tokenA,$siteA,$blockA,$unitA,$residentA,$dueItemA,$docA] = $this->bootstrapGraph('legal5@example.com');
        [$tokenB,$siteB,$blockB,$unitB,$residentB,$dueItemB,$docB] = $this->bootstrapGraph('legal6@example.com');

        $caseA=$this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/legal-cases/',['site_id'=>$siteA,'unit_id'=>$unitA,'resident_profile_id'=>$residentA,'case_type'=>'enforcement']);
        $caseIdA=(int)json_decode($caseA->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];

        $this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseIdA.'/debts',['due_item_id'=>$dueItemB])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseIdA.'/documents',['document_id'=>$docB,'document_type'=>'petition'])->assertStatus(403);
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseIdA.'/events',['event_type'=>'note','event_date'=>date('Y-m-d H:i:s'),'description'=>'ok'])->assertStatus(200);

        $debtA=$this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseIdA.'/debts',['due_item_id'=>$dueItemA,'interest_amount'=>12.5]);
        $debtIdA=(int)json_decode($debtA->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->delete('/api/v1/legal-case-debts/'.$debtIdA)->assertStatus(403);

        $docAttachA=$this->withHeaders(['Authorization'=>'Bearer '.$tokenA])->withBodyFormat('json')->post('/api/v1/legal-cases/'.$caseIdA.'/documents',['document_id'=>$docA,'document_type'=>'petition']);
        $caseDocumentIdA=(int)json_decode($docAttachA->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->delete('/api/v1/legal-case-documents/'.$caseDocumentIdA)->assertStatus(403);
    }

    /** @return array{0:string,1:int,2:int,3:int,4:int,5:int,6:int} */
    private function bootstrapGraph(string $email): array
    {
        [$emailCreated,$userId,$companyId] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Legal Site', 'code' => 'LGL' . random_int(10, 99)]);
        $siteId = (int) json_decode($site->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $block = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/blocks/', ['site_id' => $siteId, 'name' => 'B1', 'code' => 'B1']);
        $blockId = (int) json_decode($block->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $floor = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/floors/', ['site_id' => $siteId, 'block_id' => $blockId, 'number' => 1]);
        $floorId = (int) json_decode($floor->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $unit = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/units/', ['site_id' => $siteId, 'block_id' => $blockId, 'floor_id' => $floorId, 'unit_no' => '11']);
        $unitId = (int) json_decode($unit->getJSON(), true, 512, JSON_THROW_ON_ERROR)['data']['id'];
        $db=Database::connect(); $now=date('Y-m-d H:i:s');
        $db->table('resident_profiles')->insert(['company_id'=>$companyId,'user_id'=>$userId,'first_name'=>'Res','last_name'=>'One','status'=>'active','created_at'=>$now,'updated_at'=>$now]); $residentId=(int)$db->insertID();
        $db->table('unit_occupancies')->insert(['company_id'=>$companyId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'relationship_type'=>'owner','start_date'=>date('Y-m-d'),'status'=>'active','created_at'=>$now,'updated_at'=>$now]);
        $db->table('due_definitions')->insert(['company_id'=>$companyId,'site_id'=>$siteId,'block_id'=>$blockId,'name'=>'Aidat','code'=>'AIDAT','calculation_type'=>'fixed','amount'=>100.00,'currency'=>'TRY','status'=>'active','created_at'=>$now,'updated_at'=>$now]); $dueDefinitionId=(int)$db->insertID();
        $db->table('due_periods')->insert(['company_id'=>$companyId,'site_id'=>$siteId,'period_key'=>date('Y-m'),'start_date'=>date('Y-m-01'),'end_date'=>date('Y-m-t'),'due_date'=>date('Y-m-t'),'status'=>'draft','created_at'=>$now,'updated_at'=>$now]); $duePeriodId=(int)$db->insertID();
        $db->table('due_items')->insert(['company_id'=>$companyId,'site_id'=>$siteId,'block_id'=>$blockId,'floor_id'=>$floorId,'unit_id'=>$unitId,'due_definition_id'=>$dueDefinitionId,'due_period_id'=>$duePeriodId,'description'=>'Ocak aidati','amount'=>100.00,'paid_amount'=>0.00,'remaining_amount'=>100.00,'currency'=>'TRY','due_date'=>date('Y-m-t'),'status'=>'unpaid','created_at'=>$now,'updated_at'=>$now]); $dueItemId=(int)$db->insertID();
        $db->table('documents')->insert(['company_id'=>$companyId,'site_id'=>$siteId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'title'=>'Ihtar','document_type'=>'legal','visibility'=>'management','status'=>'active','created_at'=>$now,'updated_at'=>$now]); $documentId=(int)$db->insertID();
        return [$token,$siteId,$blockId,$unitId,$residentId,$dueItemId,$documentId];
    }

    /** @return array{0:string,1:int,2:int} */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect(); $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(), 'name' => 'Legal Co ' . bin2hex(random_bytes(2)), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]); $companyId = (int) $db->insertID();
        $db->table('users')->insert(['company_id' => $companyId, 'public_id' => $this->uuid(), 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'first_name' => 'Legal', 'last_name' => 'User', 'status' => 'active', 'is_active' => 1, 'failed_login_count' => 0, 'locked_until' => null, 'created_at' => $now, 'updated_at' => $now]); $userId = (int) $db->insertID();
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
