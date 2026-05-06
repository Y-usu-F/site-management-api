<?php

namespace App\Validation;

class ResidentProfileValidation
{
    public static function createRules(): array
    {
        return [
            'company_id' => 'permit_empty|is_natural_no_zero',
            'user_id' => 'permit_empty|is_natural_no_zero',
            'first_name' => 'required|string|min_length[2]|max_length[100]',
            'last_name' => 'required|string|min_length[2]|max_length[100]',
            'identity_number' => 'permit_empty|string|max_length[30]',
            'phone' => 'permit_empty|string|max_length[30]',
            'email' => 'permit_empty|valid_email|max_length[190]',
            'birth_date' => 'permit_empty|valid_date[Y-m-d]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'user_id' => 'permit_empty|is_natural_no_zero',
            'first_name' => 'permit_empty|string|min_length[2]|max_length[100]',
            'last_name' => 'permit_empty|string|min_length[2]|max_length[100]',
            'identity_number' => 'permit_empty|string|max_length[30]',
            'phone' => 'permit_empty|string|max_length[30]',
            'email' => 'permit_empty|valid_email|max_length[190]',
            'birth_date' => 'permit_empty|valid_date[Y-m-d]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
