<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class MeterReadingModel extends TenantAwareModel
{
    protected $table = 'meter_readings';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'meter_id',
        'reading_period_id',
        'previous_index',
        'current_index',
        'consumption',
        'unit_price',
        'amount',
        'reading_date',
        'source',
        'status',
        'submitted_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejected_reason',
        'photo_path',
        'created_by',
        'updated_by',
    ];
}
