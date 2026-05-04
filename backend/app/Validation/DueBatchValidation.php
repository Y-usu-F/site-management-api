<?php

namespace App\Validation;

class DueBatchValidation
{
    public static function createRules(): array
    {
        return [
            'due_definition_id' => 'required|is_natural_no_zero',
            'due_period_id' => 'required|is_natural_no_zero',
        ];
    }
}
