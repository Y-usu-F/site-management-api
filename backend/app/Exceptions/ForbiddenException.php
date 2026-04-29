<?php

namespace App\Exceptions;

final class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Bu islem icin yetkiniz yok')
    {
        parent::__construct($message, 'FORBIDDEN', 403);
    }
}
