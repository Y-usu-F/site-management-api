<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\StaffAssignmentService;
use App\Validation\StaffAssignmentValidation;
use Throwable;
class StaffAssignmentController extends ApiController
{
    public function __construct(private readonly StaffAssignmentService $service = new StaffAssignmentService()) {}
    public function index(){try{return $this->ok('Staff assignment listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffAssignmentValidation::createRules()); return $this->ok('Staff assignment olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Staff assignment getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],StaffAssignmentValidation::updateRules()); return $this->ok('Staff assignment guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($id=null){try{$this->service->delete((int)$id); return $this->ok('Staff assignment silindi',['id'=>(int)$id]);}catch(Throwable $e){return $this->failFromException($e);}}
}
