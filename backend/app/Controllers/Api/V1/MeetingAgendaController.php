<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\MeetingAgendaService;
use App\Validation\MeetingAgendaValidation;
use Throwable;
class MeetingAgendaController extends ApiController
{
    public function __construct(private readonly MeetingAgendaService $service = new MeetingAgendaService()) {}
    public function index($meetingId=null){try{return $this->ok('Meeting agenda listesi getirildi',$this->service->listByMeeting((int)$meetingId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($meetingId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],MeetingAgendaValidation::createRules()); return $this->ok('Meeting agenda olusturuldu',$this->service->create((int)$meetingId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($itemId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],MeetingAgendaValidation::updateRules()); return $this->ok('Meeting agenda guncellendi',$this->service->update((int)$itemId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($itemId=null){try{$this->service->delete((int)$itemId); return $this->ok('Meeting agenda silindi',['id'=>(int)$itemId]);}catch(Throwable $e){return $this->failFromException($e);}}
}
