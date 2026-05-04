<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\AssetMaintenancePlanService;
use App\Validation\AssetMaintenancePlanValidation;
use Throwable;

class AssetMaintenancePlanController extends ApiController
{
    public function __construct(private readonly AssetMaintenancePlanService $service = new AssetMaintenancePlanService()) {}
    public function index(){try{return $this->ok('Asset maintenance plan listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],AssetMaintenancePlanValidation::createRules()); return $this->ok('Asset maintenance plan olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Asset maintenance plan getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],AssetMaintenancePlanValidation::updateRules()); return $this->ok('Asset maintenance plan guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function pause($id=null){try{return $this->ok('Asset maintenance plan paused',$this->service->pause((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function resume($id=null){try{return $this->ok('Asset maintenance plan resumed',$this->service->resume((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Asset maintenance plan cancelled',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
