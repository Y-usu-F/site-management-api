<?php

namespace App\Filters;

use App\Services\Common\AuditLogService;
use App\Services\Security\RateLimitService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\SecurityConfig;

class RateLimitFilter implements FilterInterface
{
    public function __construct(
        private readonly RateLimitService $rateLimitService = new RateLimitService(),
        private readonly SecurityConfig $securityConfig = new SecurityConfig(),
        private readonly AuditLogService $auditLogService = new AuditLogService()
    ) {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $endpoint = strtolower((string) ($arguments[0] ?? 'generic'));
        [$maxAttempts, $windowSeconds] = $this->resolveLimits($endpoint);

        $result = $this->rateLimitService->consume($endpoint, $request, $maxAttempts, $windowSeconds);
        if ($result['allowed']) {
            return null;
        }

        if ($endpoint === 'login') {
            $payload = $request instanceof IncomingRequest ? $request->getJSON(true) : null;
            $email = is_array($payload) ? ($payload['email'] ?? null) : null;
            $userAgent = $request instanceof IncomingRequest
                ? $request->getUserAgent()->getAgentString()
                : '';
            $this->auditLogService->recordEvent('auth.login.blocked_rate_limit', [
                'status' => 'failed',
                'ip' => $request->getIPAddress(),
                'user_agent' => $userAgent,
                'meta' => [
                    'endpoint' => $endpoint,
                    'retry_after' => $result['retry_after'],
                    'email' => $this->rateLimitService->normalizeEmail(is_string($email) ? $email : null),
                    'backend' => $result['backend'],
                ],
            ]);
        }

        $response = api_response(service('response'), false, 'Cok fazla istek gonderildi', null, [
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $result['retry_after'],
        ], 429);

        return $response->setHeader('Retry-After', (string) $result['retry_after']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolveLimits(string $endpoint): array
    {
        return match ($endpoint) {
            'login' => [$this->securityConfig->rateLimitLoginMaxAttempts, $this->securityConfig->rateLimitLoginWindowSeconds],
            'forgot-password' => [$this->securityConfig->rateLimitForgotPasswordMaxAttempts, $this->securityConfig->rateLimitForgotPasswordWindowSeconds],
            'reset-password' => [$this->securityConfig->rateLimitResetPasswordMaxAttempts, $this->securityConfig->rateLimitResetPasswordWindowSeconds],
            default => [10, 60],
        };
    }
}
