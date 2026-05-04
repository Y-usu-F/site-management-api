<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class WorkOrderModel extends TenantAwareModel
{
    protected $table = 'work_orders';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'service_request_id',
        'assigned_to_user_id',
        'vendor_name',
        'status',
        'planned_start_at',
        'planned_end_at',
        'started_at',
        'completed_at',
        'cost_amount',
        'currency',
        'notes',
        'created_by',
        'updated_by',
    ];
}
