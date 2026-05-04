<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class VehicleAccessListModel extends TenantAwareModel
{
    protected $table='vehicle_access_lists'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','site_id','unit_id','plate_number','list_type','reason','status','created_by','updated_by'];
}
