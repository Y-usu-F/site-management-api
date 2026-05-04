<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ServiceRequestFileModel extends TenantAwareModel
{
    protected $table = 'service_request_files';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'service_request_id',
        'file_name',
        'file_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];
}
