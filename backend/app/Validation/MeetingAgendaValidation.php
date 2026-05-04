<?php
namespace App\Validation;
class MeetingAgendaValidation
{
    public static function createRules(): array { return ['item_no'=>'permit_empty|is_natural_no_zero','title'=>'required|string|min_length[2]|max_length[220]','description'=>'permit_empty|string']; }
    public static function updateRules(): array { return ['item_no'=>'permit_empty|is_natural_no_zero','title'=>'permit_empty|string|min_length[2]|max_length[220]','description'=>'permit_empty|string']; }
}
