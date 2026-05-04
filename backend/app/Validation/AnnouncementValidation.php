<?php

namespace App\Validation;

class AnnouncementValidation
{
    public static function createRules(): array
    {
        return [
            'title' => 'required|string|min_length[3]|max_length[200]',
            'body' => 'required|string|min_length[3]',
            'publish_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'expires_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'targets' => 'permit_empty',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'title' => 'permit_empty|string|min_length[3]|max_length[200]',
            'body' => 'permit_empty|string|min_length[3]',
            'publish_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'expires_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ];
    }
}
