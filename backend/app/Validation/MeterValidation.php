<?php

namespace App\Validation;

class MeterValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'meter_no' => 'permit_empty|string|max_length[80]',
            'meter_type' => 'required|in_list[electricity,water,natural_gas,heat,other]',
            'scope' => 'required|in_list[site,block,unit]',
            'name' => 'permit_empty|string|max_length[150]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'meter_no' => 'permit_empty|string|max_length[80]',
            'meter_type' => 'permit_empty|in_list[electricity,water,natural_gas,heat,other]',
            'scope' => 'permit_empty|in_list[site,block,unit]',
            'name' => 'permit_empty|string|max_length[150]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
