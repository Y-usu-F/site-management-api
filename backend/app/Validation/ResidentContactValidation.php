<?php

namespace App\Validation;

class ResidentContactValidation
{
    public static function createRules(): array
    {
        return [
            'resident_profile_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[phone,email,emergency]',
            'label' => 'permit_empty|string|max_length[100]',
            'value' => 'required|string|max_length[255]',
            'is_primary' => 'permit_empty',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'type' => 'permit_empty|in_list[phone,email,emergency]',
            'label' => 'permit_empty|string|max_length[100]',
            'value' => 'permit_empty|string|max_length[255]',
            'is_primary' => 'permit_empty',
        ];
    }
}
