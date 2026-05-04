<?php
namespace Tests\Feature\Operation;
use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
final class DocumentManagementTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;


    public function testCategoryDocumentVersionRuleFlowCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,$residentId,$staffId,$userId] = $this->bootstrapGraph('doc1.' . bin2hex(random_bytes(4)) . '@example.com');
        $cat=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/document-categories/',['name'=>'Sozlesme','code'=>'contracts']);
        $cat->assertStatus(200);
        $catId=(int)json_decode($cat->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $doc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/',['category_id'=>$catId,'site_id'=>$siteId,'block_id'=>$blockId,'unit_id'=>$unitId,'resident_profile_id'=>$residentId,'staff_profile_id'=>$staffId,'title'=>'Belge 1','document_type'=>'contract','visibility'=>'private']);
        $doc->assertStatus(200);
        $docId=(int)json_decode($doc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/'.$docId.'/versions',['file_name'=>'v1.pdf','file_path'=>'/docs/v1.pdf','checksum'=>'abc'])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/'.$docId.'/versions',['file_name'=>'v2.pdf','file_path'=>'/docs/v2.pdf','checksum'=>'abc'])->assertStatus(409);
        [$emailOther,$otherUserId] = $this->createUserWithRole('doc.other.' . bin2hex(random_bytes(4)) . '@example.com', 'Password123!');
        $this->login($emailOther, 'Password123!');
        $rule=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/'.$docId.'/access-rules',['rule_type'=>'user','rule_value'=>(string)$otherUserId,'permission'=>'view']);
        $rule->assertStatus(200);
        $rule2=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/'.$docId.'/access-rules',['rule_type'=>'user','rule_value'=>(string)$userId,'permission'=>'view']);
        $rule2->assertStatus(200);
        $ruleId=(int)json_decode($rule2->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/'.$docId.'/access-rules',['rule_type'=>'user','rule_value'=>(string)$userId,'permission'=>'view'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->delete('/api/v1/document-access-rules/'.$ruleId)->assertStatus(200);
        $audit=Database::connect()->table('audit_logs')->where('event','document.version.create.success')->orderBy('id','DESC')->get(1)->getRowArray();
        $this->assertIsArray($audit);
        $this->assertStringNotContainsString('/docs/', (string)($audit['new_values']??''));
    }

    public function testStateMachineAuditCrossTenantCalisir(): void
    {
        [$token,$siteId,$blockId,$unitId,,,,$emailA] = $this->bootstrapGraph('doc2.' . bin2hex(random_bytes(4)) . '@example.com');
        [$tokenB,$siteIdB,$blockIdB,$unitIdB,,,$userB,$emailB] = $this->bootstrapGraph('doc3.' . bin2hex(random_bytes(4)) . '@example.com');
        $doc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/',['site_id'=>$siteId,'block_id'=>$blockId,'unit_id'=>$unitId,'title'=>'Belge 2','document_type'=>'invoice','visibility'=>'management']);
        $docId=(int)json_decode($doc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->get('/api/v1/documents/'.$docId)->assertStatus(403);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/documents/'.$docId,['title'=>'Belge 2x'])->assertStatus(200);
        $auditUpd=Database::connect()->table('audit_logs')->where('event','document.document.update.success')->where('entity_id',(string)$docId)->orderBy('id','DESC')->get(1)->getRowArray();
        $this->assertIsArray($auditUpd);
        $old=json_decode((string)($auditUpd['old_values']??'{}'),true);
        $new=json_decode((string)($auditUpd['new_values']??'{}'),true);
        $this->assertSame('Belge 2',$old['title']??null);
        $this->assertSame('Belge 2x',$new['title']??null);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/documents/'.$docId.'/archive')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->put('/api/v1/documents/'.$docId,['title'=>'xx'])->assertStatus(409);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/documents/'.$docId.'/restore')->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->delete('/api/v1/documents/'.$docId)->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->post('/api/v1/documents/'.$docId.'/restore')->assertStatus(409);

        // visibility policies
        $privateDoc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/',['site_id'=>$siteId,'block_id'=>$blockId,'unit_id'=>$unitId,'title'=>'Priv','document_type'=>'legal','visibility'=>'private']);
        $privateId=(int)json_decode($privateDoc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->get('/api/v1/documents/'.$privateId)->assertStatus(403);
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/'.$privateId.'/access-rules',['rule_type'=>'user','rule_value'=>(string)$userB,'permission'=>'view'])->assertStatus(200);
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->get('/api/v1/documents/'.$privateId)->assertStatus(200);

        $publicDoc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/',['site_id'=>$siteId,'block_id'=>$blockId,'unit_id'=>$unitId,'title'=>'Pub','document_type'=>'other','visibility'=>'public']);
        $publicId=(int)json_decode($publicDoc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$tokenB])->get('/api/v1/documents/'.$publicId)->assertStatus(403); // cross-tenant still blocked

        // residents visibility: doc2 user has resident profile in its tenant and unit relation, so should see
        $resDoc=$this->withHeaders(['Authorization'=>'Bearer '.$token])->withBodyFormat('json')->post('/api/v1/documents/',['site_id'=>$siteId,'block_id'=>$blockId,'unit_id'=>$unitId,'title'=>'Res','document_type'=>'meeting','visibility'=>'residents']);
        $resId=(int)json_decode($resDoc->getJSON(),true,512,JSON_THROW_ON_ERROR)['data']['id'];
        $this->withHeaders(['Authorization'=>'Bearer '.$token])->get('/api/v1/documents/'.$resId)->assertStatus(200);
    }

    /** @return array{0:string,1:int,2:int,3:int,4:int,5:int,6:int,7:string} */
    private function bootstrapGraph(string $email): array
    {
        [$emailCreated,$userId,$companyId] = $this->createUserWithRole($email, 'Password123!');
        $token = (string) $this->login($emailCreated, 'Password123!')['data']['access_token'];
        $site = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')->post('/api/v1/sites/', ['name' => 'Doc Site', 'code' => 'DOC' . random_int(10, 99)]);
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
        $db->table('staff_profiles')->insert(['company_id'=>$companyId,'user_id'=>$userId,'first_name'=>'Staff','last_name'=>'One','staff_type'=>'management','status'=>'active','created_at'=>$now,'updated_at'=>$now]);
        $staffId=(int)$db->insertID();
        return [$token,$siteId,$blockId,$unitId,$residentId,$staffId,$userId,$emailCreated];
    }

    /** @return array{0:string,1:int,2:int} */
    private function createUserWithRole(string $email, string $password): array
    {
        $db = Database::connect(); $now = date('Y-m-d H:i:s');
        $db->table('companies')->insert(['public_id' => $this->uuid(), 'name' => 'Doc Co ' . bin2hex(random_bytes(2)), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]); $companyId = (int) $db->insertID();
        $db->table('users')->insert(['company_id' => $companyId, 'public_id' => $this->uuid(), 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'first_name' => 'Doc', 'last_name' => 'User', 'status' => 'active', 'is_active' => 1, 'failed_login_count' => 0, 'locked_until' => null, 'created_at' => $now, 'updated_at' => $now]); $userId = (int) $db->insertID();
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
