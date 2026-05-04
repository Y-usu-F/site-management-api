<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\StaffProfileService;
use App\Validation\StaffProfileValidation;
use Throwable;
class StaffProfileController extends ApiController
{
    public function __construct(private readonly StaffProfileService $service = new StaffProfileService()) {}
    public function index(){try{return $this->ok('Staff profile listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffProfileValidation::createRules()); return $this->ok('Staff profile olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Staff profile getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffProfileValidation::updateRules()); return $this->ok('Staff profile guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Staff profile silindi',['id'=>(int)$id]);}catch(Throwable $e){return $this->failFromException($e);}}
}
