<?php
namespace App\Services\Operation;
use App\Core\BaseService;
use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\LegalCaseDocumentModel;
use Config\Database;
class LegalCaseDocumentService extends BaseService
{
    public function __construct(private readonly LegalCaseDocumentModel $model = new LegalCaseDocumentModel(), private readonly LegalCaseService $caseService = new LegalCaseService()) { parent::__construct(); }
    public function listByCase(int $caseId,array $query=[]): array { $this->caseService->show($caseId); $q=ListQuery::normalize($query,['sortable'=>['id','document_type','created_at'],'filterable'=>['document_type']]); $b=$this->model->builder()->select('*')->where('legal_case_id',$caseId)->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i); }
    public function create(int $caseId,array $payload): array { $this->caseService->show($caseId); $docId=(int)$payload['document_id']; $this->assertDocumentTenant($docId); $dup=$this->model->builder()->select('id')->where('legal_case_id',$caseId)->where('document_id',$docId)->where('deleted_at',null)->get(1)->getRowArray(); if($dup!==null){throw new ConflictApiException('Ayni case + document duplicate olamaz');} $d=['legal_case_id'=>$caseId,'document_id'=>$docId,'document_type'=>(string)$payload['document_type']]; $this->model->insert($d,true); $id=(int)$this->model->getInsertID(); $n=$this->show($id); $this->audit('legal.legal_case_document.create.success',['entity_type'=>'legal_case_document','entity_id'=>$id,'new_values'=>$n]); return $n; }
    public function delete(int $id): void { $old=$this->show($id); $this->model->delete($id); $this->audit('legal.legal_case_document.delete.success',['entity_type'=>'legal_case_document','entity_id'=>$id,'old_values'=>$old]); }
    public function show(int $id): array { $r=Database::connect()->table('legal_case_documents')->where('id',$id)->get(1)->getRowArray(); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Legal case document bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0&&(int)$r['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} return $r; }
    private function assertDocumentTenant(int $documentId): void { $doc=Database::connect()->table('documents')->where('id',$documentId)->where('deleted_at',null)->get(1)->getRowArray(); if(!is_array($doc)){throw new NotFoundApiException('Document bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0&&(int)$doc['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');} }
}
