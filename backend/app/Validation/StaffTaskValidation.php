<?php
namespace App\Validation;
class StaffTaskValidation
{
    public static function createRules(): array { return ['staff_profile_id'=>'permit_empty|is_natural_no_zero','site_id'=>'required|is_natural_no_zero','work_order_id'=>'permit_empty|is_natural_no_zero','title'=>'required|string|min_length[2]|max_length[200]','description'=>'permit_empty|string','priority'=>'permit_empty|in_list[low,normal,high,urgent]','status'=>'permit_empty|in_list[open,assigned,in_progress,completed,cancelled]','due_at'=>'permit_empty|valid_date','proof_note'=>'permit_empty|string','proof_file_path'=>'permit_empty|string|max_length[255]']; }
    public static function updateRules(): array { return ['staff_profile_id'=>'permit_empty|is_natural_no_zero','site_id'=>'permit_empty|is_natural_no_zero','work_order_id'=>'permit_empty|is_natural_no_zero','title'=>'permit_empty|string|min_length[2]|max_length[200]','description'=>'permit_empty|string','priority'=>'permit_empty|in_list[low,normal,high,urgent]','status'=>'permit_empty|in_list[open,assigned,in_progress,completed,cancelled]','due_at'=>'permit_empty|valid_date','proof_note'=>'permit_empty|string','proof_file_path'=>'permit_empty|string|max_length[255]']; }
    public static function assignRules(): array { return ['staff_profile_id'=>'required|is_natural_no_zero']; }
    public static function completeRules(): array { return ['proof_note'=>'permit_empty|string','proof_file_path'=>'permit_empty|string|max_length[255]']; }
}
