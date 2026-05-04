<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\LegalCaseEventService;
use App\Validation\LegalCaseEventValidation;
use Throwable;
class LegalCaseEventController extends ApiController
{
    public function __construct(private readonly LegalCaseEventService $service = new LegalCaseEventService()) {}
    public function index($caseId=null){try{return $this->ok('Legal case event listesi getirildi',$this->service->listByCase((int)$caseId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($caseId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],LegalCaseEventValidation::createRules()); return $this->ok('Legal case event olusturuldu',$this->service->create((int)$caseId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
}
