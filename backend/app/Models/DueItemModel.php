<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class DueItemModel extends TenantAwareModel
{
    protected $table = 'due_items';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'block_id',
        'floor_id',
        'unit_id',
        'due_definition_id',
        'due_period_id',
        'due_batch_id',
        'description',
        'amount',
        'paid_amount',
        'remaining_amount',
        'currency',
        'due_date',
        'status',
        'created_by',
        'updated_by',
    ];
}
