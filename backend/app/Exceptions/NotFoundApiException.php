<?php

namespace App\Exceptions;

final class NotFoundApiException extends ApiException
{
    public function __construct(string $message = 'Kayit bulunamadi')
    {
        parent::__construct($message, 'NOT_FOUND', 404);
    }
}
