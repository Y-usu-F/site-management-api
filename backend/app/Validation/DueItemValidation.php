<?php

namespace App\Validation;

class DueItemValidation
{
    public static function updateRules(): array
    {
        return [
            'description' => 'permit_empty|string|max_length[500]',
            'paid_amount' => 'permit_empty|decimal',
            'status' => 'permit_empty|in_list[unpaid,partial,paid,cancelled]',
        ];
    }
}
