<?php

namespace Config;

use RuntimeException;

final class EnvValidator
{
    private const WEAK_SECRETS = [
        '',
        'default',
        'local',
        'test',
        'testing',
        'changeme',
        'change-me',
        'secret',
        'jwt-secret',
        'local-dev-jwt-secret-change-me',
    ];

    public static function validateOrFail(): void
    {
        static $validated = false;
        if ($validated) {
            return;
        }

        $validator = new self();
        $validator->validateAppEnv();
        $validator->validateBaseUrl();
        $validator->validateJwt();
        $validator->validateDatabase();

        $validated = true;
    }

    private function validateAppEnv(): void
    {
        $appEnv = strtolower(trim((string) ($this->readEnv('APP_ENV') ?? ENVIRONMENT)));
        if ($appEnv === '') {
            $this->fail('APP_ENV tanimli degil.');
        }
    }

    private function validateBaseUrl(): void
    {
        $baseUrl = trim((string) ($this->readEnv('APP_BASE_URL') ?? $this->readEnv('app.baseURL') ?? ''));
        if ($baseUrl === '') {
            $this->fail('APP_BASE_URL tanimli degil.');
        }
        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $this->fail('APP_BASE_URL gecerli bir URL olmali.');
        }
    }

    private function validateJwt(): void
    {
        $jwtSecret = trim((string) ($this->envFirst(['JWT_SECRET', 'auth.jwtSecret']) ?? ''));
        if ($jwtSecret === '') {
            $this->fail('JWT_SECRET tanimli degil.');
        }

        if (in_array(strtolower($jwtSecret), self::WEAK_SECRETS, true)) {
            $this->fail('JWT_SECRET zayif veya varsayilan deger olamaz.');
        }

        if (strlen($jwtSecret) < 32) {
            $this->fail('JWT_SECRET en az 32 karakter olmali.');
        }

        $accessTtlRaw = $this->envFirst(['JWT_ACCESS_TTL', 'auth.jwtAccessTtl']);
        $refreshTtlRaw = $this->envFirst(['JWT_REFRESH_TTL', 'auth.jwtRefreshTtl']);
        if ($accessTtlRaw === null || ! $this->isPositiveInt($accessTtlRaw)) {
            $this->fail('JWT_ACCESS_TTL pozitif tam sayi olmali.');
        }
        if ($refreshTtlRaw === null || ! $this->isPositiveInt($refreshTtlRaw)) {
            $this->fail('JWT_REFRESH_TTL pozitif tam sayi olmali.');
        }

        $accessTtl = (int) $accessTtlRaw;
        $refreshTtl = (int) $refreshTtlRaw;
        if ($refreshTtl <= $accessTtl) {
            $this->fail('JWT_REFRESH_TTL, JWT_ACCESS_TTL degerinden buyuk olmali.');
        }
    }

    private function validateDatabase(): void
    {
        $isTesting = ENVIRONMENT === 'testing';
        $driver = strtolower((string) ($this->envFirst(['DB_CONNECTION', 'database.default.DBDriver']) ?? 'mysqli'));
        if ($isTesting && $driver === 'sqlite3') {
            return;
        }

        $host = trim((string) ($this->envFirst(['DB_HOST', 'database.default.hostname']) ?? ''));
        $name = trim((string) ($this->envFirst(['DB_DATABASE', 'database.default.database']) ?? ''));
        $user = trim((string) ($this->envFirst(['DB_USERNAME', 'database.default.username']) ?? ''));

        if ($host === '' || $name === '' || $user === '') {
            $this->fail('DATABASE baglanti degerleri eksik. DB_HOST, DB_DATABASE ve DB_USERNAME zorunludur.');
        }
    }

    private function envFirst(array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = $this->readEnv($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function readEnv(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function isPositiveInt(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }
        if (! is_string($value) || ! preg_match('/^\d+$/', trim($value))) {
            return false;
        }

        return (int) $value > 0;
    }

    private function fail(string $message): never
    {
        throw new RuntimeException('[ENV_VALIDATION_ERROR] ' . $message);
    }
}
