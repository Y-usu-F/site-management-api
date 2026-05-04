<?php

namespace App\Validation;

class NotificationTemplateValidation
{
    public static function createRules(): array
    {
        return ['code' => 'required|string|max_length[100]', 'channel' => 'required|in_list[in_app,email,sms]', 'locale' => 'permit_empty|string|max_length[10]', 'subject' => 'permit_empty|string|max_length[200]', 'body' => 'required|string', 'status' => 'permit_empty|in_list[active,passive]'];
    }
    public static function updateRules(): array
    {
        return ['code' => 'permit_empty|string|max_length[100]', 'channel' => 'permit_empty|in_list[in_app,email,sms]', 'locale' => 'permit_empty|string|max_length[10]', 'subject' => 'permit_empty|string|max_length[200]', 'body' => 'permit_empty|string', 'status' => 'permit_empty|in_list[active,passive]'];
    }
}
