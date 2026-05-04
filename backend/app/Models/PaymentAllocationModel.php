<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class PaymentAllocationModel extends TenantAwareModel
{
    protected $table = 'payment_allocations';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'payment_id',
        'due_item_id',
        'amount',
        'created_by',
        'updated_by',
    ];
}
