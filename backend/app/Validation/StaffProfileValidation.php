<?php
namespace App\Validation;
class StaffProfileValidation
{
    public static function createRules(): array { return ['user_id'=>'permit_empty|is_natural_no_zero','first_name'=>'required|string|min_length[2]|max_length[120]','last_name'=>'required|string|min_length[2]|max_length[120]','phone'=>'permit_empty|string|max_length[40]','email'=>'permit_empty|valid_email|max_length[120]','staff_type'=>'required|in_list[security,cleaning,technical,garden,management,other]','status'=>'permit_empty|in_list[active,passive]']; }
    public static function updateRules(): array { return ['user_id'=>'permit_empty|is_natural_no_zero','first_name'=>'permit_empty|string|min_length[2]|max_length[120]','last_name'=>'permit_empty|string|min_length[2]|max_length[120]','phone'=>'permit_empty|string|max_length[40]','email'=>'permit_empty|valid_email|max_length[120]','staff_type'=>'permit_empty|in_list[security,cleaning,technical,garden,management,other]','status'=>'permit_empty|in_list[active,passive]']; }
}
