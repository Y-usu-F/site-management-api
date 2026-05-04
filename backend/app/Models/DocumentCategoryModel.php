<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class DocumentCategoryModel extends TenantAwareModel
{
    protected $table='document_categories'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','name','code','status','created_by','updated_by'];
}
