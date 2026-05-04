<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class NotificationRecipientModel extends TenantAwareModel
{
    protected $table = 'notification_recipients';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'message_id', 'user_id', 'resident_profile_id', 'email', 'phone', 'status', 'read_at', 'created_by', 'updated_by'];
}
