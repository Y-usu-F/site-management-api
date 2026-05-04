<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Exceptions\ConflictApiException;
use App\Services\Operation\AssetService;
use App\Validation\AssetValidation;
use Throwable;

class AssetController extends ApiController
{
    public function __construct(private readonly AssetService $service = new AssetService()) {}
    public function index(){try{return $this->ok('Asset listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],AssetValidation::createRules()); return $this->ok('Asset olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Asset getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$row=$this->service->show((int)$id); if((string)($row['status']??'')==='retired'){throw new ConflictApiException('retired asset guncellenemez');} $p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],AssetValidation::updateRules()); return $this->ok('Asset guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Asset silindi',['id'=>(int)$id]);}catch(Throwable $e){return $this->failFromException($e);}}
}
