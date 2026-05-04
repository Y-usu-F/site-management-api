<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class StaffTaskModel extends TenantAwareModel
{
    protected $table='staff_tasks'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','staff_profile_id','site_id','work_order_id','title','description','priority','status','due_at','completed_at','proof_note','proof_file_path','created_by','updated_by'];
}
