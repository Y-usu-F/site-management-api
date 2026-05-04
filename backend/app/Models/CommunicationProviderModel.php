<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class CommunicationProviderModel extends TenantAwareModel
{
    protected $table = 'communication_providers';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'channel', 'provider_name', 'config_json', 'is_default', 'status', 'created_by', 'updated_by'];
}
