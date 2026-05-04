<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\DocumentCategoryService;
use App\Validation\DocumentCategoryValidation;
use Throwable;
class DocumentCategoryController extends ApiController
{
    public function __construct(private readonly DocumentCategoryService $service = new DocumentCategoryService()) {}
    public function index(){try{return $this->ok('Document category listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DocumentCategoryValidation::createRules()); return $this->ok('Document category olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Document category getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DocumentCategoryValidation::updateRules()); return $this->ok('Document category guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Document category silindi',['id'=>(int)$id]);}catch(Throwable $e){return $this->failFromException($e);}}
}
