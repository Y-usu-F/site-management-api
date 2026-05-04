<?php
namespace App\Validation;
class DecisionBookValidation
{
    public static function createRules(): array { return ['meeting_id'=>'permit_empty|is_natural_no_zero','decision_date'=>'required|valid_date[Y-m-d]','subject'=>'required|string|min_length[2]|max_length[220]','decision_text'=>'required|string','vote_result'=>'required|in_list[unanimous,majority,rejected,informational]','document_id'=>'permit_empty|is_natural_no_zero']; }
    public static function updateRules(): array { return ['meeting_id'=>'permit_empty|is_natural_no_zero','decision_date'=>'permit_empty|valid_date[Y-m-d]','subject'=>'permit_empty|string|min_length[2]|max_length[220]','decision_text'=>'permit_empty|string','vote_result'=>'permit_empty|in_list[unanimous,majority,rejected,informational]','document_id'=>'permit_empty|is_natural_no_zero']; }
}
