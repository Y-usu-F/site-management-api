<?php

namespace App\Exceptions;

final class ConflictApiException extends ApiException
{
    public function __construct(string $message = 'Cakisan kayit')
    {
        parent::__construct($message, 'CONFLICT', 409);
    }
}
