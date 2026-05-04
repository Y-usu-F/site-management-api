<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController;
use App\Services\Operation\MeetingAttendeeService;
use App\Validation\MeetingAttendeeValidation;
use Throwable;
class MeetingAttendeeController extends ApiController
{
    public function __construct(private readonly MeetingAttendeeService $service = new MeetingAttendeeService()) {}
    public function index($meetingId=null){try{return $this->ok('Meeting attendee listesi getirildi',$this->service->listByMeeting((int)$meetingId,$this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}}
    public function create($meetingId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],MeetingAttendeeValidation::createRules()); return $this->ok('Meeting attendee olusturuldu',$this->service->create((int)$meetingId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function update($attendeeId=null){try{$p=$this->apiValidator->validateOrFail($this->request->getJSON(true)??[],MeetingAttendeeValidation::updateRules()); return $this->ok('Meeting attendee guncellendi',$this->service->update((int)$attendeeId,$p));}catch(Throwable $e){return $this->failFromException($e);}}
    public function sign($attendeeId=null){try{return $this->ok('Meeting attendee imzalandi',$this->service->sign((int)$attendeeId));}catch(Throwable $e){return $this->failFromException($e);}}
    public function delete($attendeeId=null){try{$this->service->delete((int)$attendeeId); return $this->ok('Meeting attendee silindi',['id'=>(int)$attendeeId]);}catch(Throwable $e){return $this->failFromException($e);}}
}
