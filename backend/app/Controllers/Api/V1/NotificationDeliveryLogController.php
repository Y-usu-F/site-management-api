<?php
namespace App\Controllers\Api\V1;
use App\Core\ApiController; use App\Services\Communication\NotificationDeliveryLogService; use Throwable;
class NotificationDeliveryLogController extends ApiController { public function __construct(private readonly NotificationDeliveryLogService $service=new NotificationDeliveryLogService()){} public function index(){try{return $this->ok('Notification delivery log listesi getirildi',$this->service->list($this->request->getGet()));}catch(Throwable $e){return $this->failFromException($e);}} public function show($id=null){try{return $this->ok('Notification delivery log getirildi',$this->service->show((int)$id));}catch(Throwable $e){return $this->failFromException($e);}} }
