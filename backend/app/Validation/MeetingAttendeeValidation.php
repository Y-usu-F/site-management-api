<?php
namespace App\Validation;
class MeetingAttendeeValidation
{
    public static function createRules(): array { return ['resident_profile_id'=>'permit_empty|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','attendance_type'=>'required|in_list[owner,tenant,proxy,board_member,auditor,guest]','proxy_for_resident_id'=>'permit_empty|is_natural_no_zero','land_share'=>'permit_empty|decimal','vote_weight'=>'permit_empty|decimal','status'=>'permit_empty|in_list[invited,attended,absent,cancelled]']; }
    public static function updateRules(): array { return ['resident_profile_id'=>'permit_empty|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','attendance_type'=>'permit_empty|in_list[owner,tenant,proxy,board_member,auditor,guest]','proxy_for_resident_id'=>'permit_empty|is_natural_no_zero','land_share'=>'permit_empty|decimal','vote_weight'=>'permit_empty|decimal','status'=>'permit_empty|in_list[invited,attended,absent,cancelled]']; }
}
