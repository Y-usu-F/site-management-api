<?php
namespace App\Validation;
class LegalCaseValidation
{
    public static function createRules(): array { return ['site_id'=>'required|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','resident_profile_id'=>'permit_empty|is_natural_no_zero','case_type'=>'required|in_list[warning,legal_notice,enforcement,lawsuit]','lawyer_name'=>'permit_empty|string|max_length[160]','enforcement_office'=>'permit_empty|string|max_length[160]','file_number'=>'permit_empty|string|max_length[120]','expense_amount'=>'permit_empty|decimal','opened_at'=>'permit_empty|valid_date','notes'=>'permit_empty|string']; }
    public static function updateRules(): array { return ['site_id'=>'permit_empty|is_natural_no_zero','unit_id'=>'permit_empty|is_natural_no_zero','resident_profile_id'=>'permit_empty|is_natural_no_zero','case_type'=>'permit_empty|in_list[warning,legal_notice,enforcement,lawsuit]','status'=>'permit_empty|in_list[prepared,in_progress]','lawyer_name'=>'permit_empty|string|max_length[160]','enforcement_office'=>'permit_empty|string|max_length[160]','file_number'=>'permit_empty|string|max_length[120]','expense_amount'=>'permit_empty|decimal','opened_at'=>'permit_empty|valid_date','notes'=>'permit_empty|string']; }
}
