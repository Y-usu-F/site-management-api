<?php

namespace App\Services\Communication;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Libraries\ListQuery;
use App\Models\NotificationDeliveryLogModel;
use Config\Database;

class NotificationDeliveryLogService extends BaseService
{
    public function __construct(private readonly NotificationDeliveryLogModel $model = new NotificationDeliveryLogModel()) { parent::__construct(); }
    public function list(array $query): array { $q=ListQuery::normalize($query,['sortable'=>['id','attempted_at','status'],'filterable'=>['message_id','channel','status']]); $b=$this->model->builder()->select('*')->where('deleted_at',null); foreach($q['filters'] as $f=>$v){$b->where($f,$v);} $t=(int)$b->countAllResults(false); $i=$b->orderBy($q['sort'],$q['direction'])->limit($q['per_page'],($q['page']-1)*$q['per_page'])->get()->getResultArray(); return ListQuery::envelope($q['page'],$q['per_page'],$t,$i);}
    public function show(int $id): array { $this->assertAccessible($id); $r=$this->model->tenantFind($id); if(!is_array($r)||($r['deleted_at']??null)!==null){throw new NotFoundApiException('Notification delivery log bulunamadi');} return $r; }
    private function assertAccessible(int $id): void { $row=Database::connect()->table('notification_delivery_logs')->where('id',$id)->get()->getRowArray(); if(!is_array($row)||($row['deleted_at']??null)!==null){throw new NotFoundApiException('Notification delivery log bulunamadi');} $ctx=(int)(service('request')->company_id??0); if($ctx>0 && (int)$row['company_id']!==$ctx){throw new TenantAccessDeniedException('Cross-tenant erisim engellendi');}}
}
