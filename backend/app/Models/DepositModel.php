<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class DepositModel extends TenantAwareModel
{
    protected $table = 'deposits';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'unit_id',
        'resident_profile_id',
        'deposit_no',
        'initial_amount',
        'balance_amount',
        'currency',
        'status',
        'received_at',
        'closed_at',
        'notes',
        'created_by',
        'updated_by',
    ];
}
