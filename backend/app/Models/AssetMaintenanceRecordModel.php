<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AssetMaintenanceRecordModel extends TenantAwareModel
{
    protected $table = 'asset_maintenance_records';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'asset_id', 'maintenance_plan_id', 'work_order_id', 'performed_at', 'performed_by', 'vendor_name', 'cost_amount', 'currency', 'description', 'next_due_date', 'status', 'created_by', 'updated_by'];
}
