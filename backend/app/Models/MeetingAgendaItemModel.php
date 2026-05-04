<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class MeetingAgendaItemModel extends TenantAwareModel
{
    protected $table='meeting_agenda_items'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','meeting_id','item_no','title','description','created_by','updated_by'];
}
