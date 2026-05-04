<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class SecurityIncidentModel extends TenantAwareModel
{
    protected $table='security_incidents'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','site_id','unit_id','title','description','severity','status','reported_by','resolved_at','closed_at','created_by','updated_by'];
}
