<?php

namespace App\Validation;

class PaymentValidation
{
    public static function manualCreateRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'idempotency_key' => 'permit_empty|string|max_length[120]',
            'amount' => 'required|decimal|greater_than[0]',
            'currency' => 'permit_empty|string|max_length[10]',
            'payment_date' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'method' => 'required|in_list[cash,bank_transfer,credit_card,online]',
            'description' => 'permit_empty|string|max_length[500]',
        ];
    }
}
