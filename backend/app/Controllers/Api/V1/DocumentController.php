<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\DocumentService;
use App\Validation\DocumentValidation;
use Throwable;
class DocumentController extends ApiController
{
    public function __construct(private readonly DocumentService $service = new DocumentService()) {}
    public function index(){try{return $this->ok('Document listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DocumentValidation::createRules()); return $this->ok('Document olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Document getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DocumentValidation::updateRules()); return $this->ok('Document guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function archive($id=null){try{return $this->ok('Document arsivlendi',$this->service->archive((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function restore($id=null){try{return $this->ok('Document geri yuklendi',$this->service->restore((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Document silindi',['id'=>(int)$id]);}catch(Throwable $e){return $this->failFromException($e);}}
}
