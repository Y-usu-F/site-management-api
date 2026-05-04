<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ConsumptionReportModel extends TenantAwareModel
{
    protected $table = 'consumption_reports';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'reading_id',
        'unit_id',
        'due_item_id',
        'status',
        'amount',
        'created_by',
        'updated_by',
    ];
}
