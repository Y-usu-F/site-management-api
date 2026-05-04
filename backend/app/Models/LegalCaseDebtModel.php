<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class LegalCaseDebtModel extends TenantAwareModel
{
    protected $table='legal_case_debts'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','legal_case_id','due_item_id','principal_amount','interest_amount','total_amount','status','created_by','updated_by'];
}
