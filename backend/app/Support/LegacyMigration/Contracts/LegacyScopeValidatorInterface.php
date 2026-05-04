<?php

namespace App\Support\LegacyMigration\Contracts;

use App\Support\LegacyMigration\DryRunReport;

interface LegacyScopeValidatorInterface
{
    /**
     * @param array<string,string> $options
     */
    public function validate(DryRunReport $report, array $options): DryRunReport;
}

