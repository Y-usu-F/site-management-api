<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class DueBatchModel extends TenantAwareModel
{
    protected $table = 'due_batches';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'due_definition_id',
        'due_period_id',
        'batch_key',
        'total_units',
        'total_amount',
        'status',
        'error_message',
        'created_by',
        'updated_by',
    ];
}
