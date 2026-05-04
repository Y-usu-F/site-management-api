<?php
namespace App\Validation;
class LegalCaseEventValidation
{
    public static function createRules(): array { return ['event_type'=>'required|in_list[created,notice_sent,lawyer_assigned,filed,payment_received,note,status_changed,closed,cancelled]','event_date'=>'required|valid_date','description'=>'permit_empty|string']; }
}
