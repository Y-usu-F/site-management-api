<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\LegalCaseDocumentService;
use App\Validation\LegalCaseDocumentValidation;
use Throwable;
class LegalCaseDocumentController extends ApiController
{
    public function __construct(private readonly LegalCaseDocumentService $service = new LegalCaseDocumentService()) {}
    public function index($caseId=null){try{return $this->ok('Legal case document listesi getirildi',$this->service->listByCase((int)$caseId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($caseId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],LegalCaseDocumentValidation::createRules()); return $this->ok('Legal case document olusturuldu',$this->service->create((int)$caseId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($caseDocumentId=null){try{$this->service->delete((int)$caseDocumentId); return $this->ok('Legal case document silindi',['id'=>(int)$caseDocumentId]);}catch(Throwable $e){return $this->failFromException($e);}}
}
