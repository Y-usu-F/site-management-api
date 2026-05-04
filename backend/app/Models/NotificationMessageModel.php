<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class NotificationMessageModel extends TenantAwareModel
{
    protected $table = 'notification_messages';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'template_id', 'channel', 'subject', 'body', 'payload_json', 'status', 'scheduled_at', 'sent_at', 'created_by', 'updated_by'];
}
