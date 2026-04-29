<?php

namespace App\Validation;

class AuthValidation
{
    /**
     * @return array<string, string>
     */
    public static function loginRules(): array
    {
        return [
            'email' => 'required|valid_email|max_length[190]',
            'password' => 'required|min_length[8]|max_length[128]',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function refreshRules(): array
    {
        return [
            'refresh_token' => 'required|max_length[4096]',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forgotPasswordRules(): array
    {
        return [
            'email' => 'required|valid_email|max_length[190]',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function resetPasswordRules(): array
    {
        return [
            'token' => 'required|string|min_length[16]|max_length[255]',
            'password' => 'required|min_length[8]|max_length[128]',
        ];
    }
}
