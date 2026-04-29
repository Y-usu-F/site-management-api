<?php

namespace App\Exceptions;

final class TenantAccessDeniedException extends ApiException
{
    public function __construct(string $message = 'Tenant erisimi reddedildi')
    {
        parent::__construct($message, 'FORBIDDEN', 403);
    }
}
