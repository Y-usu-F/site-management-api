<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\DecisionBookService;
use App\Validation\DecisionBookValidation;
use Throwable;
class DecisionBookController extends ApiController
{
    public function __construct(private readonly DecisionBookService $service = new DecisionBookService()) {}
    public function index(){try{return $this->ok('Decision book listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DecisionBookValidation::createRules()); return $this->ok('Decision book kaydi olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Decision book kaydi getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DecisionBookValidation::updateRules()); return $this->ok('Decision book kaydi guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function approve($id=null){try{return $this->ok('Decision approve edildi',$this->service->approve((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function lock($id=null){try{return $this->ok('Decision lock edildi',$this->service->lock((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Decision iptal edildi',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
