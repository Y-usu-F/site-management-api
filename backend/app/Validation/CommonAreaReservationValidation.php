<?php

namespace App\Validation;

class CommonAreaReservationValidation
{
    public static function createRules(): array
    {
        return ['common_area_id' => 'required|is_natural_no_zero', 'unit_id' => 'permit_empty|is_natural_no_zero', 'resident_profile_id' => 'permit_empty|is_natural_no_zero', 'start_at' => 'required|valid_date', 'end_at' => 'required|valid_date', 'participant_count' => 'permit_empty|is_natural_no_zero', 'notes' => 'permit_empty|string'];
    }
    public static function updateRules(): array
    {
        return ['unit_id' => 'permit_empty|is_natural_no_zero', 'resident_profile_id' => 'permit_empty|is_natural_no_zero', 'start_at' => 'permit_empty|valid_date', 'end_at' => 'permit_empty|valid_date', 'participant_count' => 'permit_empty|is_natural_no_zero', 'notes' => 'permit_empty|string'];
    }
}
