<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AuditLogModel extends TenantAwareModel
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected bool $requiresTenant = false;
    protected $allowedFields = [
        'company_id',
        'user_id',
        'action',
        'event',
        'actor_user_id',
        'target_user_id',
        'status',
        'ip',
        'ip_address',
        'user_agent',
        'request_id',
        'occurred_at',
        'entity_type',
        'entity_id',
        'old_data',
        'new_data',
        'old_values',
        'new_values',
        'meta',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps = true;
}
