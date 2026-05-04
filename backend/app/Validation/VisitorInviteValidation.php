<?php
namespace App\Validation;
class VisitorInviteValidation
{
    public static function createRules(): array { return ['site_id'=>'required|is_natural_no_zero','unit_id'=>'required|is_natural_no_zero','resident_profile_id'=>'permit_empty|is_natural_no_zero','visitor_name'=>'required|string|min_length[2]|max_length[150]','visitor_phone'=>'permit_empty|string|max_length[40]','vehicle_plate'=>'permit_empty|string|max_length[20]','valid_from'=>'required|valid_date','valid_until'=>'required|valid_date','max_uses'=>'permit_empty|is_natural_no_zero','notes'=>'permit_empty|string']; }
}
