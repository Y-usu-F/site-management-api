<?php
namespace App\Validation;
class StaffAssignmentValidation
{
    public static function createRules(): array { return ['staff_profile_id'=>'required|is_natural_no_zero','site_id'=>'required|is_natural_no_zero','block_id'=>'permit_empty|is_natural_no_zero','role_note'=>'permit_empty|string','start_date'=>'required|valid_date[Y-m-d]','end_date'=>'permit_empty|valid_date[Y-m-d]','status'=>'permit_empty|in_list[active,passive]']; }
    public static function updateRules(): array { return ['staff_profile_id'=>'permit_empty|is_natural_no_zero','site_id'=>'permit_empty|is_natural_no_zero','block_id'=>'permit_empty|is_natural_no_zero','role_note'=>'permit_empty|string','start_date'=>'permit_empty|valid_date[Y-m-d]','end_date'=>'permit_empty|valid_date[Y-m-d]','status'=>'permit_empty|in_list[active,passive]']; }
}
