<?php
namespace App\Validation;
class VisitorEntryValidation
{
    public static function checkInRules(): array { return ['visitor_invite_id'=>'permit_empty|is_natural_no_zero','site_id'=>'permit_empty|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','visitor_name'=>'permit_empty|string|min_length[2]|max_length[150]','visitor_phone'=>'permit_empty|string|max_length[40]','vehicle_plate'=>'permit_empty|string|max_length[20]','entry_type'=>'required|in_list[invite,manual]','recorded_by'=>'permit_empty|string|max_length[120]','notes'=>'permit_empty|string']; }
    public static function checkOutRules(): array { return ['entry_id'=>'required|is_natural_no_zero','recorded_by'=>'permit_empty|string|max_length[120]','notes'=>'permit_empty|string']; }
}
