<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ApiDocsConfig extends BaseConfig
{
    public bool $enabled = true;
    public string $specFile = APPPATH . 'Docs/openapi.yaml';

    public function __construct()
    {
        parent::__construct();

        $defaultEnabled = strtolower((string) env('CI_ENVIRONMENT', 'production')) !== 'production';
        $this->enabled = filter_var((string) env('API_DOCS_ENABLED', $defaultEnabled ? '1' : '0'), FILTER_VALIDATE_BOOL);
    }
}
