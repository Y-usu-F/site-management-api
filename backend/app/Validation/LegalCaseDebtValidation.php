<?php
namespace App\Validation;
class LegalCaseDebtValidation
{
    public static function createRules(): array { return ['due_item_id'=>'required|is_natural_no_zero','interest_amount'=>'permit_empty|decimal']; }
}
