<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class FloorModel extends TenantAwareModel
{
    protected $table = 'floors';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'block_id',
        'number',
        'label',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];
}
