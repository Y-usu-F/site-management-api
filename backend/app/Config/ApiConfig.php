<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ApiConfig extends BaseConfig
{
    public string $versionPrefix = 'api/v1';
    public int $defaultPerPage = 20;
    public int $maxPerPage = 100;
    public bool $maskSensitiveLogFields = true;

    /**
     * Kullaniciya veri varligini sizdirmamak icin korumali varsayim.
     */
    public bool $hideEntityExistenceOnDenied = true;
}
