<?php

namespace App\Validation;

class WorkOrderValidation
{
    public static function createRules(): array
    {
        return [
            'service_request_id' => 'required|is_natural_no_zero',
            'assigned_to_user_id' => 'permit_empty|is_natural_no_zero',
            'vendor_name' => 'permit_empty|string|max_length[150]',
            'planned_start_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'planned_end_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'cost_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'currency' => 'permit_empty|string|max_length[10]',
            'notes' => 'permit_empty|string',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'assigned_to_user_id' => 'permit_empty|is_natural_no_zero',
            'vendor_name' => 'permit_empty|string|max_length[150]',
            'planned_start_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'planned_end_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'cost_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'currency' => 'permit_empty|string|max_length[10]',
            'notes' => 'permit_empty|string',
        ];
    }
}
