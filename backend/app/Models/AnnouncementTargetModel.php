<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AnnouncementTargetModel extends TenantAwareModel
{
    protected $table = 'announcement_targets';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'announcement_id',
        'target_type',
        'target_id',
        'created_by',
        'updated_by',
    ];
}
