<?php

namespace App\Exceptions;

final class AuthorizationException extends ApiException
{
    /**
     * @param array{allowed:bool,reason:?string,permission:string,scope:string,is_super_admin:bool}|null $decision
     */
    public function __construct(
        string $message = 'Bu islem icin yetkiniz yok',
        private readonly ?array $decision = null
    ) {
        parent::__construct($message, 'FORBIDDEN', 403);
    }

    /**
     * @return array{allowed:bool,reason:?string,permission:string,scope:string,is_super_admin:bool}|null
     */
    public function getDecision(): ?array
    {
        return $this->decision;
    }
}

