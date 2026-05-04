<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class MeterReadingPeriodModel extends TenantAwareModel
{
    protected $table = 'meter_reading_periods';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'period_key',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
    ];
}
