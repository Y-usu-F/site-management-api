<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\CommonAreaReservationService;
use App\Validation\CommonAreaReservationValidation;
use Throwable;

class CommonAreaReservationController extends ApiController
{
    public function __construct(private readonly CommonAreaReservationService $service = new CommonAreaReservationService()) {}
    public function index(){try{return $this->ok('Reservation listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],CommonAreaReservationValidation::createRules()); return $this->ok('Reservation olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Reservation getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],CommonAreaReservationValidation::updateRules()); return $this->ok('Reservation guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function approve($id=null){try{return $this->ok('Reservation approve edildi',$this->service->approve((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function reject($id=null){try{$r=($this->request->getJSON(true)??[])['rejected_reason']??null; return $this->ok('Reservation reject edildi',$this->service->reject((int)$id,$r!==null?(string)$r:null));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{$r=($this->request->getJSON(true)??[])['cancelled_reason']??null; return $this->ok('Reservation cancel edildi',$this->service->cancel((int)$id,$r!==null?(string)$r:null));}catch(Throwable $e){return $this->failFromException($e);}}
    public function complete($id=null){try{return $this->ok('Reservation complete edildi',$this->service->complete((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
