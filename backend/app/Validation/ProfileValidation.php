<?php

namespace App\Validation;

class ProfileValidation
{
    /**
     * @return array<string, string>
     */
    public static function updateRules(): array
    {
        return [
            'first_name' => 'permit_empty|string|min_length[2]|max_length[100]',
            'last_name' => 'permit_empty|string|min_length[2]|max_length[100]',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function changePasswordRules(): array
    {
        return [
            'current_password' => 'required|min_length[8]|max_length[128]',
            'new_password' => 'required|min_length[8]|max_length[128]',
        ];
    }
}
