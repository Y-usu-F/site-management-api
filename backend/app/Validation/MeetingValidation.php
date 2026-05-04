<?php
namespace App\Validation;
class MeetingValidation
{
    public static function createRules(): array { return ['site_id'=>'required|is_natural_no_zero','meeting_type'=>'required|in_list[general_assembly,extraordinary_general_assembly,board_meeting]','title'=>'required|string|min_length[2]|max_length[220]','description'=>'permit_empty|string','meeting_date'=>'required|valid_date','location'=>'permit_empty|string|max_length[255]']; }
    public static function updateRules(): array { return ['site_id'=>'permit_empty|is_natural_no_zero','meeting_type'=>'permit_empty|in_list[general_assembly,extraordinary_general_assembly,board_meeting]','title'=>'permit_empty|string|min_length[2]|max_length[220]','description'=>'permit_empty|string','meeting_date'=>'permit_empty|valid_date','location'=>'permit_empty|string|max_length[255]']; }
}
