<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class DepositTransactionModel extends TenantAwareModel
{
    protected $table = 'deposit_transactions';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'deposit_id',
        'transaction_type',
        'amount',
        'currency',
        'due_item_id',
        'payment_id',
        'description',
        'transaction_date',
        'created_by',
        'updated_by',
    ];
}
