<?php

namespace App\Validation;

class BlockValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'name' => 'required|string|min_length[1]|max_length[150]',
            'code' => 'required|alpha_numeric_punct|min_length[1]|max_length[50]',
            'sort_order' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'name' => 'permit_empty|string|min_length[1]|max_length[150]',
            'code' => 'permit_empty|alpha_numeric_punct|min_length[1]|max_length[50]',
            'sort_order' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
