<?php

namespace App\Validation;

class FloorValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'block_id' => 'required|is_natural_no_zero',
            'number' => 'required|integer',
            'label' => 'permit_empty|string|max_length[50]',
            'sort_order' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'number' => 'permit_empty|integer',
            'label' => 'permit_empty|string|max_length[50]',
            'sort_order' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
