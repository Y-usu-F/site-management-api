<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\AssetMaintenanceRecordService;
use App\Validation\AssetMaintenanceRecordValidation;
use Throwable;

class AssetMaintenanceRecordController extends ApiController
{
    public function __construct(private readonly AssetMaintenanceRecordService $service = new AssetMaintenanceRecordService()) {}
    public function index(){try{return $this->ok('Asset maintenance record listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],AssetMaintenanceRecordValidation::createRules()); return $this->ok('Asset maintenance record olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Asset maintenance record getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Asset maintenance record cancelled',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
