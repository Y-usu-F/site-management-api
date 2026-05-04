<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class RequestCategoryModel extends TenantAwareModel
{
    protected $table = 'request_categories';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'name',
        'code',
        'status',
        'created_by',
        'updated_by',
    ];
}
