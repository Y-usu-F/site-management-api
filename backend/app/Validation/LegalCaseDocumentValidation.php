<?php
namespace App\Validation;
class LegalCaseDocumentValidation
{
    public static function createRules(): array { return ['document_id'=>'required|is_natural_no_zero','document_type'=>'required|in_list[notice,debt_statement,power_of_attorney,court_document,enforcement_document,petition,other]']; }
}
