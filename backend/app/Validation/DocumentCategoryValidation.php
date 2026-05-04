<?php
namespace App\Validation;
class DocumentCategoryValidation
{
    public static function createRules(): array { return ['name'=>'required|string|min_length[2]|max_length[160]','code'=>'permit_empty|string|max_length[80]','status'=>'permit_empty|in_list[active,passive]']; }
    public static function updateRules(): array { return ['name'=>'permit_empty|string|min_length[2]|max_length[160]','code'=>'permit_empty|string|max_length[80]','status'=>'permit_empty|in_list[active,passive]']; }
}
