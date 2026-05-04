<?php

namespace App\Validation;

class ServiceRequestCommentValidation
{
    public static function createRules(): array
    {
        return [
            'comment' => 'required|string|min_length[1]',
            'visibility' => 'permit_empty|in_list[internal,public]',
        ];
    }
}
