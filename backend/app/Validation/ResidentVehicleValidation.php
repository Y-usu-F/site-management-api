<?php

namespace App\Validation;

class ResidentVehicleValidation
{
    public static function createRules(): array
    {
        return [
            'resident_profile_id' => 'required|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'plate_number' => 'required|string|min_length[3]|max_length[20]',
            'brand' => 'permit_empty|string|max_length[80]',
            'model' => 'permit_empty|string|max_length[80]',
            'color' => 'permit_empty|string|max_length[50]',
            'parking_slot' => 'permit_empty|string|max_length[50]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'plate_number' => 'permit_empty|string|min_length[3]|max_length[20]',
            'brand' => 'permit_empty|string|max_length[80]',
            'model' => 'permit_empty|string|max_length[80]',
            'color' => 'permit_empty|string|max_length[50]',
            'parking_slot' => 'permit_empty|string|max_length[50]',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
