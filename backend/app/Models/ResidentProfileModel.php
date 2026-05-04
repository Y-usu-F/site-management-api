<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ResidentProfileModel extends TenantAwareModel
{
    protected $table = 'resident_profiles';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'user_id',
        'first_name',
        'last_name',
        'identity_number',
        'phone',
        'email',
        'birth_date',
        'status',
        'created_by',
        'updated_by',
    ];
}
