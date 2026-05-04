<?php

namespace App\Validation;

class ServiceRequestValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'category_id' => 'permit_empty|is_natural_no_zero',
            'title' => 'required|string|min_length[3]|max_length[200]',
            'description' => 'required|string|min_length[3]',
            'priority' => 'permit_empty|in_list[low,normal,high,urgent]',
            'source' => 'permit_empty|in_list[panel,mobile,admin]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'block_id' => 'permit_empty|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'category_id' => 'permit_empty|is_natural_no_zero',
            'title' => 'permit_empty|string|min_length[3]|max_length[200]',
            'description' => 'permit_empty|string|min_length[3]',
            'priority' => 'permit_empty|in_list[low,normal,high,urgent]',
            'source' => 'permit_empty|in_list[panel,mobile,admin]',
        ];
    }

    public static function assignRules(): array
    {
        return [
            'assigned_to_user_id' => 'required|is_natural_no_zero',
        ];
    }
}
