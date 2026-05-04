<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController; use App\Services\Operation\VehicleAccessListService; use App\Validation\VehicleAccessListValidation; use Throwable;
class VehicleAccessListController extends ApiController
{
    public function __construct(private readonly VehicleAccessListService $service = new VehicleAccessListService()){}
    public function index(){try{return $this->ok('Vehicle access listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],VehicleAccessListValidation::createRules()); return $this->ok('Vehicle access kaydi olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Vehicle access kaydi getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],VehicleAccessListValidation::updateRules()); return $this->ok('Vehicle access kaydi guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Vehicle access kaydi silindi',['id'=>(int)$id]);}catch(Throwable $e){return $this->failFromException($e);}}
}
