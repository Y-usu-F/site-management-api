<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class MeterModel extends TenantAwareModel
{
    protected $table = 'meters';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'block_id',
        'unit_id',
        'meter_no',
        'meter_type',
        'scope',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];
}
