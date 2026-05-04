<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class LegalCaseEventModel extends TenantAwareModel
{
    protected $table='legal_case_events'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','legal_case_id','event_type','event_date','description','created_by','updated_by'];
}
