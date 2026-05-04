<?php

namespace App\Validation;

class CommonAreaValidation
{
    public static function createRules(): array
    {
        return ['site_id' => 'required|is_natural_no_zero', 'name' => 'required|string|min_length[2]|max_length[150]', 'code' => 'permit_empty|string|max_length[80]', 'description' => 'permit_empty|string', 'capacity' => 'permit_empty|is_natural_no_zero', 'requires_approval' => 'permit_empty|in_list[0,1,true,false]', 'is_paid' => 'permit_empty|in_list[0,1,true,false]', 'fee_amount' => 'permit_empty|decimal|greater_than_equal_to[0]', 'currency' => 'permit_empty|string|max_length[10]', 'status' => 'permit_empty|in_list[active,passive]'];
    }
    public static function updateRules(): array
    {
        return ['site_id' => 'permit_empty|is_natural_no_zero', 'name' => 'permit_empty|string|min_length[2]|max_length[150]', 'code' => 'permit_empty|string|max_length[80]', 'description' => 'permit_empty|string', 'capacity' => 'permit_empty|is_natural_no_zero', 'requires_approval' => 'permit_empty|in_list[0,1,true,false]', 'is_paid' => 'permit_empty|in_list[0,1,true,false]', 'fee_amount' => 'permit_empty|decimal|greater_than_equal_to[0]', 'currency' => 'permit_empty|string|max_length[10]', 'status' => 'permit_empty|in_list[active,passive]'];
    }
}
