<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class StaffShiftModel extends TenantAwareModel
{
    protected $table='staff_shifts'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','staff_profile_id','site_id','shift_date','start_at','end_at','status','notes','created_by','updated_by'];
}
