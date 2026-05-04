<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\LegalCaseDebtService;
use App\Validation\LegalCaseDebtValidation;
use Throwable;
class LegalCaseDebtController extends ApiController
{
    public function __construct(private readonly LegalCaseDebtService $service = new LegalCaseDebtService()) {}
    public function index($caseId=null){try{return $this->ok('Legal case debt listesi getirildi',$this->service->listByCase((int)$caseId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($caseId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],LegalCaseDebtValidation::createRules()); return $this->ok('Legal case debt olusturuldu',$this->service->create((int)$caseId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($debtId=null){try{$this->service->delete((int)$debtId); return $this->ok('Legal case debt silindi',['id'=>(int)$debtId]);}catch(Throwable $e){return $this->failFromException($e);}}
}
