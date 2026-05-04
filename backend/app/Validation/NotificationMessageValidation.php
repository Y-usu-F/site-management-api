<?php

namespace App\Validation;

class NotificationMessageValidation
{
    public static function createRules(): array
    {
        return ['template_id' => 'permit_empty|is_natural_no_zero', 'channel' => 'required|in_list[in_app,email,sms]', 'subject' => 'permit_empty|string|max_length[200]', 'body' => 'permit_empty|string', 'payload_json' => 'permit_empty', 'scheduled_at' => 'permit_empty|valid_date[Y-m-d H:i:s]'];
    }
}
