<?php

namespace App\Validation;

class MeterReadingPeriodValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'period_key' => 'required|regex_match[/^\d{4}\-(0[1-9]|1[0-2])$/]',
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date' => 'required|valid_date[Y-m-d]',
            'status' => 'permit_empty|in_list[draft,open,closed,locked]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'period_key' => 'permit_empty|regex_match[/^\d{4}\-(0[1-9]|1[0-2])$/]',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'status' => 'permit_empty|in_list[draft,open,closed,locked]',
        ];
    }
}
