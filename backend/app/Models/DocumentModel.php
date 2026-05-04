<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class DocumentModel extends TenantAwareModel
{
    protected $table='documents'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','category_id','site_id','block_id','unit_id','resident_profile_id','staff_profile_id','title','description','document_type','visibility','status','created_by','updated_by'];
}
