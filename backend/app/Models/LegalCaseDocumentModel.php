<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class LegalCaseDocumentModel extends TenantAwareModel
{
    protected $table='legal_case_documents'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','legal_case_id','document_id','document_type','created_by','updated_by'];
}
