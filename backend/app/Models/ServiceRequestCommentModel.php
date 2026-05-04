<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ServiceRequestCommentModel extends TenantAwareModel
{
    protected $table = 'service_request_comments';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'service_request_id',
        'user_id',
        'comment',
        'visibility',
        'created_by',
        'updated_by',
    ];
}
