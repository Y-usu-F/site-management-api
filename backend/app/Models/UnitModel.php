<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class UnitModel extends TenantAwareModel
{
    protected $table = 'units';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'block_id',
        'floor_id',
        'unit_no',
        'type',
        'gross_area',
        'net_area',
        'land_share',
        'occupant_name',
        'status',
        'created_by',
        'updated_by',
    ];
}
