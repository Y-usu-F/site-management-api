<?php

namespace App\Validation;

class UnitOccupancyValidation
{
    public static function createRules(): array
    {
        return [
            'unit_id' => 'required|is_natural_no_zero',
            'resident_profile_id' => 'required|is_natural_no_zero',
            'relationship_type' => 'required|in_list[owner,tenant,resident,family_member]',
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'is_primary' => 'permit_empty',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'relationship_type' => 'permit_empty|in_list[owner,tenant,resident,family_member]',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'is_primary' => 'permit_empty',
            'status' => 'permit_empty|in_list[active,passive]',
        ];
    }
}
