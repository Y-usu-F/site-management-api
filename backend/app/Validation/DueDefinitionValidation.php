<?php

namespace App\Validation;

class DueDefinitionValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'name' => 'required|string|min_length[2]|max_length[150]',
            'code' => 'permit_empty|string|max_length[80]',
            'calculation_type' => 'required|in_list[fixed,unit_area,land_share,resident_count]',
            'amount' => 'required|decimal|greater_than[0]',
            'currency' => 'permit_empty|string|max_length[10]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'name' => 'permit_empty|string|min_length[2]|max_length[150]',
            'code' => 'permit_empty|string|max_length[80]',
            'calculation_type' => 'permit_empty|in_list[fixed,unit_area,land_share,resident_count]',
            'amount' => 'permit_empty|decimal|greater_than[0]',
            'currency' => 'permit_empty|string|max_length[10]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
