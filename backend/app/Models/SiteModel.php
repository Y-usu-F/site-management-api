<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class SiteModel extends TenantAwareModel
{
    protected $table = 'sites';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'public_id',
        'name',
        'code',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];
}
