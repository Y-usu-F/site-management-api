<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ResidentContactModel extends TenantAwareModel
{
    protected $table = 'resident_contacts';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'resident_profile_id',
        'type',
        'label',
        'value',
        'is_primary',
        'created_by',
        'updated_by',
    ];
}
