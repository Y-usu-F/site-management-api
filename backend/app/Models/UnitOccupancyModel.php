<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class UnitOccupancyModel extends TenantAwareModel
{
    protected $table = 'unit_occupancies';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'unit_id',
        'resident_profile_id',
        'relationship_type',
        'start_date',
        'end_date',
        'is_primary',
        'status',
        'created_by',
        'updated_by',
    ];
}
