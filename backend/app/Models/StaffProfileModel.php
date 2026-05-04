<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class StaffProfileModel extends TenantAwareModel
{
    protected $table='staff_profiles'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','user_id','first_name','last_name','phone','email','staff_type','status','created_by','updated_by'];
}
