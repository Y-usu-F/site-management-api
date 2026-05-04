<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class NotificationDeliveryLogModel extends TenantAwareModel
{
    protected $table = 'notification_delivery_logs';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'message_id', 'recipient_id', 'provider', 'channel', 'status', 'provider_reference', 'error_message', 'attempted_at', 'created_by', 'updated_by'];
}
