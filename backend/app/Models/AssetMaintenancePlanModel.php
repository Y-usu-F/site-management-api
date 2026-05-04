<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AssetMaintenancePlanModel extends TenantAwareModel
{
    protected $table = 'asset_maintenance_plans';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'asset_id', 'frequency_type', 'frequency_interval', 'next_due_date', 'vendor_name', 'notes', 'status', 'created_by', 'updated_by'];
}
