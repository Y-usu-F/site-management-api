<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Operation\CommonAreaService;
use App\Validation\CommonAreaValidation;
use Throwable;

class CommonAreaController extends ApiController
{
    public function __construct(private readonly CommonAreaService $service = new CommonAreaService()) {}
    public function index(){try{return $this->ok('Common area listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],CommonAreaValidation::createRules()); return $this->ok('Common area olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Common area getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],CommonAreaValidation::updateRules()); return $this->ok('Common area guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Common area silindi');}catch(Throwable $e){return $this->failFromException($e);}}
}
