<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController; use App\Services\Operation\VisitorInviteService; use App\Validation\VisitorInviteValidation; use Throwable;
class VisitorInviteController extends ApiController
{
    public function __construct(private readonly VisitorInviteService $service = new VisitorInviteService()){}
    public function index(){try{return $this->ok('Visitor invite listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],VisitorInviteValidation::createRules()); return $this->ok('Visitor invite olusturuldu',$this->service->create($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Visitor invite getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
    public function cancel($id=null){try{return $this->ok('Visitor invite cancel edildi',$this->service->cancel((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
