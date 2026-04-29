<?php

namespace App\Exceptions;

final class UnauthorizedException extends ApiException
{
    public function __construct(
        string $message = 'Kimlik dogrulama gerekli',
        string $errorCode = 'UNAUTHORIZED'
    )
    {
        parent::__construct($message, $errorCode, 401);
    }
}
