<?php
namespace App\Validation;
class DocumentAccessRuleValidation
{
    public static function createRules(): array { return ['rule_type'=>'required|in_list[role,user,resident,unit,site]','rule_value'=>'required|string|max_length[100]','permission'=>'required|in_list[view,download,manage]']; }
}
