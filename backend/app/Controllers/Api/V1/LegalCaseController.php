<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\LegalCaseService;
use App\Validation\LegalCaseValidation;
use Throwable;
class LegalCaseController extends ApiController
{
    public function __construct(private readonly LegalCaseService $service = new LegalCaseService()) {}
    public function index(){try{return $this->ok('Legal case listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],LegalCaseValidation::createRules()); return $this->ok('Legal case olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Legal case getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],LegalCaseValidation::updateRules()); return $this->ok('Legal case guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function sendToLawyer($id=null){try{return $this->ok('Legal case avukata gonderildi',$this->service->sendToLawyer((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function file($id=null){try{return $this->ok('Legal case dosyalandi',$this->service->file((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function markPaid($id=null){try{return $this->ok('Legal case odendi',$this->service->markPaid((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function close($id=null){try{return $this->ok('Legal case kapatildi',$this->service->close((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Legal case iptal edildi',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
