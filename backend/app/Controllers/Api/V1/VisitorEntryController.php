<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController; use App\Services\Operation\VisitorEntryService; use App\Validation\VisitorEntryValidation; use Throwable;
class VisitorEntryController extends ApiController
{
    public function __construct(private readonly VisitorEntryService $service = new VisitorEntryService()){}
    public function index(){try{return $this->ok('Visitor entry listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function checkIn(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],VisitorEntryValidation::checkInRules()); return $this->ok('Check-in basarili',$this->service->checkIn($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function checkOut(){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],VisitorEntryValidation::checkOutRules()); return $this->ok('Check-out basarili',$this->service->checkOut($p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($id=null){try{return $this->ok('Visitor entry getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}}
}
