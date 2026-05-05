<?php

namespace App\Services;

use App\Support\RequestRuntime;
use Config\SecurityConfig;

class RateLimitKeyService
{
    public function __construct(private readonly SecurityConfig $securityConfig = new SecurityConfig())
    {
    }

    public function build(): string
    {
        $request = service('request');

        $ip = (string) $request->getIPAddress();
        $runtimeUserId = RequestRuntime::getUserId();
        $userId = (string) ($runtimeUserId > 0 ? $runtimeUserId : 'guest');
        $endpoint = strtoupper($request->getMethod()) . ':' . trim($request->getPath(), '/');

        $raw = sprintf(
            '%s|%s|%s|%s',
            $this->securityConfig->rateLimitKeyPattern,
            $ip,
            $userId,
            $endpoint
        );

        return hash('sha256', $raw);
    }
}
