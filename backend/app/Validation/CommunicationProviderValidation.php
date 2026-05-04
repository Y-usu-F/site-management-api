<?php

namespace App\Validation;

class CommunicationProviderValidation
{
    public static function createRules(): array
    {
        return ['channel' => 'required|in_list[email,sms]', 'provider_name' => 'required|string|max_length[80]', 'config_json' => 'permit_empty', 'is_default' => 'permit_empty', 'status' => 'permit_empty|in_list[active,passive]'];
    }
    public static function updateRules(): array
    {
        return ['channel' => 'permit_empty|in_list[email,sms]', 'provider_name' => 'permit_empty|string|max_length[80]', 'config_json' => 'permit_empty', 'is_default' => 'permit_empty', 'status' => 'permit_empty|in_list[active,passive]'];
    }
}
