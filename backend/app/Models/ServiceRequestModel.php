<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ServiceRequestModel extends TenantAwareModel
{
    protected $table = 'service_requests';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'site_id',
        'block_id',
        'unit_id',
        'resident_profile_id',
        'category_id',
        'request_no',
        'title',
        'description',
        'priority',
        'status',
        'source',
        'assigned_to_user_id',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];
}
