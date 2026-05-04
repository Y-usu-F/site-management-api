<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\StaffShiftService;
use App\Validation\StaffShiftValidation;
use Throwable;
class StaffShiftController extends ApiController
{
    public function __construct(private readonly StaffShiftService $service = new StaffShiftService()) {}
    public function index(){try{return $this->ok('Staff shift listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffShiftValidation::createRules()); return $this->ok('Staff shift olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Staff shift getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffShiftValidation::updateRules()); return $this->ok('Staff shift guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function start($id=null){try{return $this->ok('Staff shift baslatildi',$this->service->start((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function complete($id=null){try{return $this->ok('Staff shift tamamlandi',$this->service->complete((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Staff shift iptal edildi',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
