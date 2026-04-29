<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        protected readonly string $errorCode = 'INTERNAL_ERROR',
        int $httpCode = 500
    ) {
        parent::__construct($message, $httpCode);
    }

    public function getHttpCode(): int
    {
        return $this->getCode();
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
