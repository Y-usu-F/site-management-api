<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController; use App\Services\Operation\SecurityIncidentService; use App\Validation\SecurityIncidentValidation; use Throwable;
class SecurityIncidentController extends ApiController
{
    public function __construct(private readonly SecurityIncidentService $service = new SecurityIncidentService()){}
    public function index(){try{return $this->ok('Security incident listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],SecurityIncidentValidation::createRules()); return $this->ok('Security incident olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Security incident getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($id=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],SecurityIncidentValidation::updateRules()); return $this->ok('Security incident guncellendi',$this->service->update((int)$id,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function resolve($id=null){try{return $this->ok('Security incident resolved',$this->service->resolve((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function close($id=null){try{return $this->ok('Security incident closed',$this->service->close((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Security incident cancelled',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
