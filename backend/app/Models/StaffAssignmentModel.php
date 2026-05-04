<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class StaffAssignmentModel extends TenantAwareModel
{
    protected $table='staff_assignments'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','staff_profile_id','site_id','block_id','role_note','start_date','end_date','status','created_by','updated_by'];
}
