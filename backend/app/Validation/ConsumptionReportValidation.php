<?php

namespace App\Validation;

class ConsumptionReportValidation
{
    public static function cancelRules(): array
    {
        return [
            'reason' => 'permit_empty|string|max_length[500]',
        ];
    }
}
