<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class DocumentVersionModel extends TenantAwareModel
{
    protected $table='document_versions'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','document_id','version_no','file_name','file_path','mime_type','size_bytes','checksum','uploaded_by','created_by','updated_by'];
}
