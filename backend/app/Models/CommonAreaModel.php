<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class CommonAreaModel extends TenantAwareModel
{
    protected $table = 'common_areas';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'site_id', 'name', 'code', 'description', 'capacity', 'requires_approval', 'is_paid', 'fee_amount', 'currency', 'status', 'created_by', 'updated_by'];
}
