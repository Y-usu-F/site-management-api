<?php

namespace App\Validation;

class SiteValidation
{
    public static function createRules(): array
    {
        return [
            'name' => 'required|string|min_length[2]|max_length[150]',
            'code' => 'required|alpha_numeric_punct|min_length[1]|max_length[50]',
            'address' => 'permit_empty|string|max_length[2000]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'permit_empty|string|min_length[2]|max_length[150]',
            'code' => 'permit_empty|alpha_numeric_punct|min_length[1]|max_length[50]',
            'address' => 'permit_empty|string|max_length[2000]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
