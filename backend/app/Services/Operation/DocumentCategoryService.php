<?php
namespace App\Services\Operation;
use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\DocumentCategoryModel;
use Config\Database;
class DocumentCategoryService extends BaseService
{
    public function __construct(private readonly DocumentCategoryModel $model = new DocumentCategoryModel()) { parent::__construct(); }
    public function list(array $query): array { $q=ListQuery::normalize($query,['sortable'=>['id','name','code','status','created_at'],'filterable'=>['status','code']]); $b=$this->model->builder()->select('*')->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} if($q['search']!==''){$b->groupStart()->like('name',$q['search'])->orLike('code',$q['search'])->groupEnd();} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $r=Database::connect()->table('document_categories')->where('id',$id)->get(1)->getRowArray(); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Document category bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0&&(int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} return $r; }
    public function create(array $payload): array { $d=$this->normalize($payload); $this->assertCodeUnique($d['code'],null); $this->model->insert($d,true); $id=(int)$this->model->getInsertID(); $n=$this->show($id); $this->audit('document.category.create.success',['entity_type'=>'document_category','entity_id'=>$id,'new_values'=>$n]); return $n; }
    public function update(int $id,array $payload): array { $old=$this->show($id); $d=$this->normalize($payload,$old); $this->assertCodeUnique($d['code'],$id); $this->model->update($id,$d); $n=$this->show($id); $this->audit('document.category.update.success',['entity_type'=>'document_category','entity_id'=>$id,'old_values'=>$old,'new_values'=>$n]); return $n; }
    public function delete(int $id): void { $old=$this->show($id); $this->model->delete($id); $this->audit('document.category.delete.success',['entity_type'=>'document_category','entity_id'=>$id,'old_values'=>$old]); }
    /** @return array<string,mixed> */ public function assertAccessible(?int $id): ?array { if($id===null){return null;} return $this->show($id); }
    /** @return array<string,mixed> */ private function normalize(array $p,array $c=[]): array { return ['name'=>trim((string)($p['name']??$c['name']??'')),'code'=>array_key_exists('code',$p)?(($p['code']??'')===''?null:strtolower(trim((string)$p['code']))):($c['code']??null),'status'=>(string)($p['status']??$c['status']??'active')]; }
    private function assertCodeUnique(?string $code,?int $exceptId): void { if($code===null||$code===''){return;} $ctx=(int)(service('request')->company_id??0); $b=$this->model->builder()->select('id')->where('company_id',$ctx)->where('code',$code)->where('deleted_at',null); if($exceptId!==null){$b->where('id !=',$exceptId);} if($b->get(1)->getRowArray()!==null){throw new ConflictApiException('category code unique olmali');} }
}
