<?php

namespace App\Validation;

class RequestCategoryValidation
{
    public static function createRules(): array
    {
        return [
            'name' => 'required|string|min_length[2]|max_length[120]',
            'code' => 'permit_empty|string|max_length[50]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'permit_empty|string|min_length[2]|max_length[120]',
            'code' => 'permit_empty|string|max_length[50]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
