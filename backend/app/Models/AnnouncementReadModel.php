<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AnnouncementReadModel extends TenantAwareModel
{
    protected $table = 'announcement_reads';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'announcement_id',
        'user_id',
        'resident_profile_id',
        'read_at',
        'created_by',
        'updated_by',
    ];
}
