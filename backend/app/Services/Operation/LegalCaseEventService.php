<?php
namespace App\Services\Operation;
use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\LegalCaseEventModel;
use Config\Database;
class LegalCaseEventService extends BaseService
{
    public function __construct(private readonly LegalCaseEventModel $model = new LegalCaseEventModel(), private readonly LegalCaseService $caseService = new LegalCaseService()) { parent::__construct(); }
    public function listByCase(int $caseId,array $query=[]): array { $this->caseService->show($caseId); $q=ListQuery::normalize($query,['sortable'=>['id','event_date','event_type','created_at'],'filterable'=>['event_type']]); $b=$this->model->builder()->select('*')->where('legal_case_id',$caseId)->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i); }
    public function create(int $caseId,array $payload): array { $this->caseService->show($caseId); $d=['legal_case_id'=>$caseId,'event_type'=>(string)$payload['event_type'],'event_date'=>(string)$payload['event_date'],'description'=>($payload['description']??'')===''?null:(string)$payload['description'],'created_by'=>(int)(service('request')->user_id??0)?:null]; $this->model->insert($d,true); $id=(int)$this->model->getInsertID(); $n=$this->show($id); $this->audit('legal.legal_case_event.create.success',['entity_type'=>'legal_case_event','entity_id'=>$id,'new_values'=>$n]); return $n; }
    public function show(int $id): array { $r=Database::connect()->table('legal_case_events')->where('id',$id)->get(1)->getRowArray(); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Legal case event bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0&&(int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} return $r; }
}
