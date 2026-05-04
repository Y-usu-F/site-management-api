<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class ResidentVehicleModel extends TenantAwareModel
{
    protected $table = 'resident_vehicles';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'resident_profile_id',
        'unit_id',
        'plate_number',
        'brand',
        'model',
        'color',
        'parking_slot',
        'status',
        'created_by',
        'updated_by',
    ];
}
