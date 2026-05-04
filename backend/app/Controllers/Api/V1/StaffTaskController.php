<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\StaffTaskService;
use App\Validation\StaffTaskValidation;
use Throwable;
class StaffTaskController extends ApiController
{
    public function __construct(private readonly StaffTaskService $service = new StaffTaskService()) {}
    public function index(){try{return $this->ok('Staff task listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffTaskValidation::createRules()); return $this->ok('Staff task olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Staff task getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffTaskValidation::updateRules()); return $this->ok('Staff task guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function assign($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffTaskValidation::assignRules()); return $this->ok('Staff task atandi',$this->service->assign((int)$id,(int)$p['staff_profile_id']));}catch(Throwable $e){return $this->failFromException($e);}}
    public function start($id=null){try{return $this->ok('Staff task baslatildi',$this->service->start((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function complete($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffTaskValidation::completeRules()); return $this->ok('Staff task tamamlandi',$this->service->complete((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Staff task iptal edildi',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
