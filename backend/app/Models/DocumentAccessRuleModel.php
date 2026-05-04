<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class DocumentAccessRuleModel extends TenantAwareModel
{
    protected $table='document_access_rules'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','document_id','rule_type','rule_value','permission','created_by','updated_by'];
}
