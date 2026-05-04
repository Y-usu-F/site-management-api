<?php

namespace App\Validation;

class UnitValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'block_id' => 'required|is_natural_no_zero',
            'floor_id' => 'required|is_natural_no_zero',
            'unit_no' => 'required|string|min_length[1]|max_length[50]',
            'type' => 'permit_empty|string|max_length[50]',
            'gross_area' => 'permit_empty|decimal',
            'net_area' => 'permit_empty|decimal',
            'occupant_name' => 'permit_empty|string|max_length[150]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'floor_id' => 'permit_empty|is_natural_no_zero',
            'unit_no' => 'permit_empty|string|min_length[1]|max_length[50]',
            'type' => 'permit_empty|string|max_length[50]',
            'gross_area' => 'permit_empty|decimal',
            'net_area' => 'permit_empty|decimal',
            'occupant_name' => 'permit_empty|string|max_length[150]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
