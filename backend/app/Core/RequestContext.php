<?php

namespace App\Core;

class RequestContext
{
    public function __construct(
        public readonly string $requestId,
        public readonly ?int $userId,
        public readonly ?int $companyId,
        public readonly string $ip,
        public readonly string $userAgent,
        public readonly string $method = '',
        public readonly string $path = '',
        public readonly string $timestamp = ''
    ) {
    }
}
