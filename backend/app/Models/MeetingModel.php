<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class MeetingModel extends TenantAwareModel
{
    protected $table='meetings'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','site_id','meeting_no','meeting_type','title','description','meeting_date','location','status','published_at','completed_at','locked_at','created_by','updated_by'];
}
