<?php
namespace App\Validation;
class StaffShiftValidation
{
    public static function createRules(): array { return ['staff_profile_id'=>'required|is_natural_no_zero','site_id'=>'required|is_natural_no_zero','shift_date'=>'required|valid_date[Y-m-d]','start_at'=>'required|valid_date','end_at'=>'required|valid_date','status'=>'permit_empty|in_list[planned,started,completed,cancelled]','notes'=>'permit_empty|string']; }
    public static function updateRules(): array { return ['staff_profile_id'=>'permit_empty|is_natural_no_zero','site_id'=>'permit_empty|is_natural_no_zero','shift_date'=>'permit_empty|valid_date[Y-m-d]','start_at'=>'permit_empty|valid_date','end_at'=>'permit_empty|valid_date','status'=>'permit_empty|in_list[planned,started,completed,cancelled]','notes'=>'permit_empty|string']; }
}
