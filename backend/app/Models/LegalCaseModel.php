<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class LegalCaseModel extends TenantAwareModel
{
    protected $table='legal_cases'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','site_id','unit_id','resident_profile_id','case_no','case_type','status','lawyer_name','enforcement_office','file_number','principal_amount','interest_amount','expense_amount','total_amount','opened_at','closed_at','notes','created_by','updated_by'];
}
