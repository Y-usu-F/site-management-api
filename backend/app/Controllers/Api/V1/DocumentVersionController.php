<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\DocumentVersionService;
use App\Validation\DocumentVersionValidation;
use Throwable;
class DocumentVersionController extends ApiController
{
    public function __construct(private readonly DocumentVersionService $service = new DocumentVersionService()) {}
    public function index($documentId=null){try{return $this->ok('Document version listesi getirildi',$this->service->listByDocument((int)$documentId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($documentId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],DocumentVersionValidation::createRules()); return $this->ok('Document version olusturuldu',$this->service->create((int)$documentId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function show($versionId=null){try{return $this->ok('Document version getirildi',$this->service->show((int)$versionId));}catch(Throwable $e){return $this->failFromException($e);}}
}
