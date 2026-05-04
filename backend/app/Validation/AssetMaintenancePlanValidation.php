<?php

namespace App\Validation;

class AssetMaintenancePlanValidation
{
    public static function createRules(): array
    {
        return ['asset_id' => 'required|is_natural_no_zero', 'frequency_type' => 'required|in_list[daily,weekly,monthly,quarterly,yearly,custom]', 'frequency_interval' => 'permit_empty|is_natural_no_zero', 'next_due_date' => 'required|valid_date[Y-m-d]', 'vendor_name' => 'permit_empty|string|max_length[160]', 'notes' => 'permit_empty|string', 'status' => 'permit_empty|in_list[active,paused,cancelled]'];
    }
    public static function updateRules(): array
    {
        return ['frequency_type' => 'permit_empty|in_list[daily,weekly,monthly,quarterly,yearly,custom]', 'frequency_interval' => 'permit_empty|is_natural_no_zero', 'next_due_date' => 'permit_empty|valid_date[Y-m-d]', 'vendor_name' => 'permit_empty|string|max_length[160]', 'notes' => 'permit_empty|string', 'status' => 'permit_empty|in_list[active,paused,cancelled]'];
    }
}
