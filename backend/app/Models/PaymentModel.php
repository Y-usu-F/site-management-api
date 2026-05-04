<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class PaymentModel extends TenantAwareModel
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'unit_id',
        'resident_profile_id',
        'payment_no',
        'provider',
        'provider_reference',
        'idempotency_key',
        'amount',
        'allocated_amount',
        'currency',
        'payment_date',
        'status',
        'method',
        'description',
        'created_by',
        'updated_by',
    ];
}
