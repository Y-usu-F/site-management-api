<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class AssetModel extends TenantAwareModel
{
    protected $table = 'assets';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'site_id', 'block_id', 'unit_id', 'asset_no', 'asset_type', 'name', 'brand', 'model', 'serial_number', 'purchase_date', 'warranty_until', 'location_note', 'status', 'created_by', 'updated_by'];
}
