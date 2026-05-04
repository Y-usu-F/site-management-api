<?php
namespace App\Validation;
class SecurityIncidentValidation
{
    public static function createRules(): array { return ['site_id'=>'required|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','title'=>'required|string|min_length[2]|max_length[200]','description'=>'permit_empty|string','severity'=>'permit_empty|in_list[low,normal,high,critical]','reported_by'=>'permit_empty|string|max_length[120]']; }
    public static function updateRules(): array { return ['title'=>'permit_empty|string|min_length[2]|max_length[200]','description'=>'permit_empty|string','severity'=>'permit_empty|in_list[low,normal,high,critical]','reported_by'=>'permit_empty|string|max_length[120]']; }
}
