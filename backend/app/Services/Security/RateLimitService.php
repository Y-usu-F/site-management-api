<?php

namespace App\Services\Security;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use Config\SecurityConfig;
use Throwable;

class RateLimitService
{
    public function __construct(
        private readonly SecurityConfig $securityConfig = new SecurityConfig()
    ) {
    }

    /**
     * @return array{allowed: bool, retry_after: int, remaining: int, key: string, backend: string}
     */
    public function consume(string $endpoint, RequestInterface $request, int $maxAttempts, int $windowSeconds): array
    {
        $key = $this->buildKey($endpoint, $request);
        $cache = cache();
        $backend = $this->resolveBackendName($cache);

        try {
            $now = time();
            $state = $cache->get($key);

            if (! is_array($state) || ! isset($state['count'], $state['reset_at'])) {
                $state = [
                    'count' => 0,
                    'reset_at' => $now + $windowSeconds,
                ];
            }

            if ((int) $state['reset_at'] <= $now) {
                $state = [
                    'count' => 0,
                    'reset_at' => $now + $windowSeconds,
                ];
            }

            $count = (int) $state['count'] + 1;
            $retryAfter = max(0, (int) $state['reset_at'] - $now);
            $allowed = $count <= $maxAttempts;
            $remaining = max(0, $maxAttempts - $count);

            $state['count'] = $count;
            $ttl = max(1, $retryAfter);
            $cache->save($key, $state, $ttl);

            return [
                'allowed' => $allowed,
                'retry_after' => $retryAfter,
                'remaining' => $remaining,
                'key' => $key,
                'backend' => $backend,
            ];
        } catch (Throwable $e) {
            // Fail-open: cache/redis sorunu auth akisini kesmez.
            log_message('error', 'Rate limit check failed: {message}', ['message' => $e->getMessage()]);

            return [
                'allowed' => true,
                'retry_after' => 0,
                'remaining' => max(0, $maxAttempts - 1),
                'key' => $key,
                'backend' => $backend,
            ];
        }
    }

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($email));
        return $normalized === '' ? null : $normalized;
    }

    public function buildKey(string $endpoint, RequestInterface $request): string
    {
        $ip = (string) $request->getIPAddress();
        $path = $request instanceof IncomingRequest ? trim($request->getPath(), '/') : '';
        $methodPath = strtoupper($request->getMethod()) . ':' . $path;
        $userAgent = $request instanceof IncomingRequest
            ? trim((string) $request->getUserAgent()->getAgentString())
            : '';
        $userAgentPart = $userAgent === '' ? 'ua:none' : 'ua:' . hash('sha256', $userAgent);

        $payload = $request instanceof IncomingRequest ? $request->getJSON(true) : null;
        $email = null;
        if (is_array($payload) && isset($payload['email'])) {
            $email = $this->normalizeEmail((string) $payload['email']);
        }

        $raw = implode('|', [
            'auth-rate-limit',
            $endpoint,
            $methodPath,
            'ip:' . $ip,
            'email:' . ($email ?? 'none'),
            $userAgentPart,
        ]);

        return 'rl_' . hash('sha256', $raw);
    }

    private function resolveBackendName(object $cache): string
    {
        $class = get_class($cache);
        if (stripos($class, 'redis') !== false) {
            return 'redis';
        }

        return 'fallback';
    }
}
