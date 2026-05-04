<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class DueDefinitionModel extends TenantAwareModel
{
    protected $table = 'due_definitions';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'block_id',
        'name',
        'code',
        'calculation_type',
        'amount',
        'currency',
        'status',
        'created_by',
        'updated_by',
    ];
}
