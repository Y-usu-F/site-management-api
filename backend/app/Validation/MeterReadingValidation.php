<?php

namespace App\Validation;

class MeterReadingValidation
{
    public static function createRules(): array
    {
        return [
            'meter_id' => 'required|is_natural_no_zero',
            'reading_period_id' => 'required|is_natural_no_zero',
            'previous_index' => 'required|decimal|greater_than_equal_to[0]',
            'current_index' => 'required|decimal|greater_than_equal_to[0]',
            'unit_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'reading_date' => 'required|valid_date[Y-m-d]',
            'source' => 'required|in_list[admin,resident,import]',
            'status' => 'permit_empty|in_list[pending,approved]',
            'photo_path' => 'permit_empty|string|max_length[255]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'previous_index' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'current_index' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'unit_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'reading_date' => 'permit_empty|valid_date[Y-m-d]',
            'source' => 'permit_empty|in_list[admin,resident,import]',
            'status' => 'permit_empty|in_list[pending]',
            'photo_path' => 'permit_empty|string|max_length[255]',
        ];
    }
}
