<?php

namespace App\Exceptions;

final class ValidationApiException extends ApiException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(string $message = 'Dogrulama hatasi', private readonly array $errors = [])
    {
        parent::__construct($message, 'VALIDATION_ERROR', 422);
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
