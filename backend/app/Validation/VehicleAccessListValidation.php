<?php
namespace App\Validation;
class VehicleAccessListValidation
{
    public static function createRules(): array { return ['site_id'=>'required|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','plate_number'=>'required|string|max_length[20]','list_type'=>'required|in_list[whitelist,blacklist]','reason'=>'permit_empty|string','status'=>'permit_empty|in_list[active,passive]']; }
    public static function updateRules(): array { return ['unit_id'=>'permit_empty|is_natural_no_zero','plate_number'=>'permit_empty|string|max_length[20]','list_type'=>'permit_empty|in_list[whitelist,blacklist]','reason'=>'permit_empty|string','status'=>'permit_empty|in_list[active,passive]']; }
}
