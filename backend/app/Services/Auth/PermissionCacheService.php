<?php

namespace App\Services\Auth;

use Config\AuthConfig;
use Throwable;

class PermissionCacheService
{
    public function __construct(
        private readonly ?object $cacheHandler = null,
        private readonly AuthConfig $authConfig = new AuthConfig()
    ) {
    }

    public function buildKey(int $userId, int $companyId, int $roleVersion): string
    {
        return sprintf('perm_%d_%d_v%d', $userId, $companyId, $roleVersion);
    }

    /**
     * @param callable():array $resolver
     * @return array<mixed>
     */
    public function rememberPermissions(int $userId, int $companyId, int $roleVersion, callable $resolver): array
    {
        if (! $this->isEnabled()) {
            return $this->resolvePermissions($resolver);
        }

        $key = $this->buildKey($userId, $companyId, $roleVersion);
        $cache = $this->resolveCache();

        try {
            $cached = $cache->get($key);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            $this->logSideEffectFailure('Permission cache read failed: {message}', $e);
            return $this->resolvePermissions($resolver);
        }

        $resolved = $this->resolvePermissions($resolver);

        try {
            $cache->save($key, $resolved, max(1, $this->authConfig->permissionCacheTtl));
        } catch (Throwable $e) {
            // Fail-open: cache problemi authorization davranisini bozmaz.
            $this->logSideEffectFailure('Permission cache write failed: {message}', $e);
        }

        return $resolved;
    }

    public function isEnabled(): bool
    {
        return $this->authConfig->permissionCacheEnabled;
    }

    public function invalidateUserCompany(int $userId, int $companyId): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $cache = $this->resolveCache();
        if (! method_exists($cache, 'deleteMatching')) {
            return;
        }

        try {
            $cache->deleteMatching(sprintf('perm_%d_%d_v*', $userId, $companyId));
        } catch (Throwable $e) {
            // Fail-open: invalidation hatasi ana auth akisini bozmamali.
            $this->logSideEffectFailure('Permission cache invalidate failed: {message}', $e);
        }
    }

    /**
     * @param callable():array $resolver
     * @return array<mixed>
     */
    private function resolvePermissions(callable $resolver): array
    {
        $result = $resolver();
        return is_array($result) ? $result : [];
    }

    private function resolveCache(): object
    {
        return $this->cacheHandler ?? cache();
    }

    private function isTestingEnvironment(): bool
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'testing';
    }

    private function logSideEffectFailure(string $message, Throwable $exception): void
    {
        log_message(
            $this->isTestingEnvironment() ? 'debug' : 'error',
            $message,
            ['message' => $exception->getMessage()]
        );
    }
}

