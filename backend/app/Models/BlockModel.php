<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class BlockModel extends TenantAwareModel
{
    protected $table = 'blocks';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'name',
        'code',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];
}
