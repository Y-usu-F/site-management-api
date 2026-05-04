<?php

namespace App\Validation;

class DepositValidation
{
    public static function createRules(): array
    {
        return [
            'site_id' => 'required|is_natural_no_zero',
            'unit_id' => 'required|is_natural_no_zero',
            'resident_profile_id' => 'required|is_natural_no_zero',
            'initial_amount' => 'required|decimal|greater_than[0]',
            'currency' => 'permit_empty|string|max_length[10]',
            'notes' => 'permit_empty|string',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'site_id' => 'permit_empty|is_natural_no_zero',
            'unit_id' => 'permit_empty|is_natural_no_zero',
            'resident_profile_id' => 'permit_empty|is_natural_no_zero',
            'notes' => 'permit_empty|string',
        ];
    }

    public static function receiveRules(): array
    {
        return [
            'transaction_date' => 'permit_empty|valid_date',
            'description' => 'permit_empty|string',
        ];
    }

    public static function refundRules(): array
    {
        return [
            'amount' => 'required|decimal|greater_than[0]',
            'transaction_date' => 'permit_empty|valid_date',
            'description' => 'permit_empty|string',
        ];
    }

    public static function deductRules(): array
    {
        return [
            'amount' => 'required|decimal|greater_than[0]',
            'transaction_date' => 'permit_empty|valid_date',
            'description' => 'permit_empty|string',
        ];
    }

    public static function applyToDebtRules(): array
    {
        return [
            'due_item_id' => 'required|is_natural_no_zero',
            'amount' => 'required|decimal|greater_than[0]',
            'transaction_date' => 'permit_empty|valid_date',
            'description' => 'permit_empty|string',
        ];
    }

    public static function cancelRules(): array
    {
        return [
            'transaction_date' => 'permit_empty|valid_date',
            'description' => 'permit_empty|string',
        ];
    }
}
