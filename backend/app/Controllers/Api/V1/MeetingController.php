<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\MeetingService;
use App\Validation\MeetingValidation;
use Throwable;
class MeetingController extends ApiController
{
    public function __construct(private readonly MeetingService $service = new MeetingService()) {}
    public function index(){try{return $this->ok('Meeting listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],MeetingValidation::createRules()); return $this->ok('Meeting olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Meeting getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],MeetingValidation::updateRules()); return $this->ok('Meeting guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function publish($id=null){try{return $this->ok('Meeting publish edildi',$this->service->publish((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function complete($id=null){try{return $this->ok('Meeting tamamlandi',$this->service->complete((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Meeting iptal edildi',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function lock($id=null){try{return $this->ok('Meeting kilitlendi',$this->service->lock((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
