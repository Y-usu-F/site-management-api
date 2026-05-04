<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class PaymentEventModel extends TenantAwareModel
{
    protected $table = 'payment_events';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'payment_id',
        'provider',
        'event_type',
        'event_key',
        'payload_json',
        'status',
        'error_message',
        'created_by',
        'updated_by',
    ];
}
