<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class TenantConfig extends BaseConfig
{
    public bool $enforce = true;
    public int $defaultCompanyId = 1;
    public string $superAdminRole = 'super_admin';
}
