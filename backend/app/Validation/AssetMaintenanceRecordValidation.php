<?php

namespace App\Validation;

class AssetMaintenanceRecordValidation
{
    public static function createRules(): array
    {
        return ['asset_id' => 'required|is_natural_no_zero', 'maintenance_plan_id' => 'permit_empty|is_natural_no_zero', 'work_order_id' => 'permit_empty|is_natural_no_zero', 'performed_at' => 'required|valid_date', 'performed_by' => 'permit_empty|string|max_length[120]', 'vendor_name' => 'permit_empty|string|max_length[160]', 'cost_amount' => 'permit_empty|decimal', 'currency' => 'permit_empty|string|max_length[10]', 'description' => 'permit_empty|string', 'next_due_date' => 'permit_empty|valid_date[Y-m-d]', 'status' => 'permit_empty|in_list[completed,cancelled]'];
    }
}
