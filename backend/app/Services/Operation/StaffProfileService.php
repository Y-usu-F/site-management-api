<?php
namespace App\Services\Operation;
use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\StaffProfileModel;
use Config\Database;
class StaffProfileService extends BaseService
{
    public function __construct(private readonly StaffProfileModel $model = new StaffProfileModel()) { parent::__construct(); }
    public function list(array $query): array { $q=ListQuery::normalize($query,['sortable'=>['id','first_name','last_name','staff_type','status','created_at'],'filterable'=>['user_id','staff_type','status']]); $b=$this->model->builder()->select('*')->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} if($q['search']!==''){$b->groupStart()->like('first_name',$q['search'])->orLike('last_name',$q['search'])->orLike('email',$q['search'])->groupEnd();} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $r=Database::connect()->table('staff_profiles')->where('id',$id)->get(1)->getRowArray(); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Staff profile bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} return $r; }
    public function create(array $payload): array { $d=$this->normalize($payload); $this->assertUserTenant($d['user_id']); $this->model->insert($d,true); $id=(int)$this->model->getInsertID(); $n=$this->show($id); $this->audit('operation.staff_profile.create.success',['entity_type'=>'staff_profile','entity_id'=>$id,'new_values'=>$n]); return $n; }
    public function update(int $id,array $payload): array { $old=$this->show($id); $d=$this->normalize($payload,$old); $this->assertUserTenant($d['user_id']); $this->model->update($id,$d); $n=$this->show($id); $this->audit('operation.staff_profile.update.success',['entity_type'=>'staff_profile','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n; }
    public function delete(int $id): void { $old=$this->show($id); $this->model->delete($id); $this->audit('operation.staff_profile.delete.success',['entity_type'=>'staff_profile','entity_id'=>$id,'old_values'=>$old]); }
    /** @return array<string,mixed> */ public function assertActive(int $id): array { $s=$this->show($id); if((string)$s['status']!=='active'){throw new ConflictApiException('staff profile aktif olmali');} return $s; }
    /** @return array<string,mixed> */ private function normalize(array $p,array $c=[]): array { return ['user_id'=>array_key_exists('user_id',$p)?($p['user_id']===''||$p['user_id']===null?null:(int)$p['user_id']):($c['user_id']??null),'first_name'=>trim((string)($p['first_name']??$c['first_name']??'')),'last_name'=>trim((string)($p['last_name']??$c['last_name']??'')),'phone'=>array_key_exists('phone',$p)?(($p['phone']??'')===''?null:trim((string)$p['phone'])):($c['phone']??null),'email'=>array_key_exists('email',$p)?(($p['email']??'')===''?null:strtolower(trim((string)$p['email']))):($c['email']??null),'staff_type'=>(string)($p['staff_type']??$c['staff_type']??''),'status'=>(string)($p['status']??$c['status']??'active')]; }
    private function assertUserTenant(?int $userId): void { if($userId===null){return;} $u=Database::connect()->table('users')->where('id',$userId)->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($u)){throw new NotFoundApiException('User bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$u['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} }
}
