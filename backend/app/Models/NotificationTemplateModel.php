<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class NotificationTemplateModel extends TenantAwareModel
{
    protected $table = 'notification_templates';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'code', 'channel', 'locale', 'subject', 'body', 'status', 'created_by', 'updated_by'];
}
