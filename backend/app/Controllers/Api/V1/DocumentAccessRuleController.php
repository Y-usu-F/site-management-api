<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\DocumentAccessRuleService;
use App\Validation\DocumentAccessRuleValidation;
use Throwable;
class DocumentAccessRuleController extends ApiController
{
    public function __construct(private readonly DocumentAccessRuleService $service = new DocumentAccessRuleService()) {}
    public function index($documentId=null){try{return $this->ok('Document access rule listesi getirildi',$this->service->listByDocument((int)$documentId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($documentId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DocumentAccessRuleValidation::createRules()); return $this->ok('Document access rule olusturuldu',$this->service->create((int)$documentId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($ruleId=null){try{$this->service->delete((int)$ruleId); return $this->ok('Document access rule silindi',['id'=>(int)$ruleId]);}catch(Throwable $e){return $this->failFromException($e);}}
}
