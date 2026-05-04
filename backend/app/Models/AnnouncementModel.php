<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AnnouncementModel extends TenantAwareModel
{
    protected $table = 'announcements';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'title',
        'body',
        'status',
        'publish_at',
        'expires_at',
        'published_at',
        'created_by',
        'updated_by',
    ];
}
