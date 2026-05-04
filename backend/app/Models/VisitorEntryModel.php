<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class VisitorEntryModel extends TenantAwareModel
{
    protected $table='visitor_entries'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','site_id','unit_id','visitor_invite_id','visitor_name','visitor_phone','vehicle_plate','entry_type','direction','entered_at','exited_at','recorded_by','notes','created_by','updated_by'];
}
