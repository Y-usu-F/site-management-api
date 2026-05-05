<?php

namespace App\Support;

final class RequestRuntime
{
    private const KEY_REQUEST_ID = 'APP_REQUEST_ID';
    private const KEY_RATE_LIMIT_KEY = 'APP_RATE_LIMIT_KEY';
    private const KEY_REQUEST_CONTEXT = 'APP_REQUEST_CONTEXT';
    private const KEY_AUTH_CONTEXT = 'APP_AUTH_CONTEXT';

    public static function setRequestId(string $requestId): void
    {
        $_SERVER[self::KEY_REQUEST_ID] = $requestId;
        $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;
    }

    public static function getRequestId(): string
    {
        return (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? $_SERVER[self::KEY_REQUEST_ID] ?? '');
    }

    public static function setRateLimitKey(string $rateLimitKey): void
    {
        $_SERVER[self::KEY_RATE_LIMIT_KEY] = $rateLimitKey;
    }

    public static function getRateLimitKey(): string
    {
        return (string) ($_SERVER[self::KEY_RATE_LIMIT_KEY] ?? '');
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function setRequestContext(array $context): void
    {
        $_SERVER[self::KEY_REQUEST_CONTEXT] = json_encode($context);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function getRequestContext(): ?array
    {
        $raw = $_SERVER[self::KEY_REQUEST_CONTEXT] ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function setAuthContext(array $context): void
    {
        $_SERVER[self::KEY_AUTH_CONTEXT] = json_encode($context);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function getAuthContext(): ?array
    {
        $raw = $_SERVER[self::KEY_AUTH_CONTEXT] ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function clearAuthContext(): void
    {
        unset($_SERVER[self::KEY_AUTH_CONTEXT]);
    }

    public static function getUserId(): int
    {
        $ctx = self::getAuthContext();
        return (int) ($ctx['user_id'] ?? 0);
    }

    public static function getCompanyId(): int
    {
        $ctx = self::getAuthContext();
        return (int) ($ctx['company_id'] ?? 0);
    }

    /**
     * @return list<string>
     */
    public static function getRoles(): array
    {
        $ctx = self::getAuthContext();
        return is_array($ctx['roles'] ?? null) ? array_values($ctx['roles']) : [];
    }

    /**
     * @return list<string>
     */
    public static function getPermissions(): array
    {
        $ctx = self::getAuthContext();
        return is_array($ctx['permissions'] ?? null) ? array_values($ctx['permissions']) : [];
    }

    public static function getSessionId(): ?int
    {
        $ctx = self::getAuthContext();
        $sessionId = $ctx['session_id'] ?? null;
        if ($sessionId === null || $sessionId === '') {
            return null;
        }
        return (int) $sessionId;
    }
}
