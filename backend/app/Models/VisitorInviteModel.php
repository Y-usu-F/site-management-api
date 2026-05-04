<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class VisitorInviteModel extends TenantAwareModel
{
    protected $table='visitor_invites'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','site_id','unit_id','resident_profile_id','invite_code','visitor_name','visitor_phone','vehicle_plate','valid_from','valid_until','max_uses','used_count','status','notes','created_by','updated_by'];
}
