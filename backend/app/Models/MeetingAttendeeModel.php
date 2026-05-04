<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class MeetingAttendeeModel extends TenantAwareModel
{
    protected $table='meeting_attendees'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','meeting_id','resident_profile_id','unit_id','attendance_type','proxy_for_resident_id','land_share','vote_weight','signed_at','status','created_by','updated_by'];
}
