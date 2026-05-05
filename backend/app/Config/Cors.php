<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use RuntimeException;

/**
 * Cross-Origin Resource Sharing (CORS) — driven by environment variables.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $default = [
        'allowedOrigins' => [],
        'allowedOriginsPatterns' => [],
        'supportsCredentials' => false,
        'allowedHeaders' => [],
        'exposedHeaders' => [],
        'allowedMethods' => [],
        'maxAge' => 7200,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->applyEnvironment();
    }

    private function applyEnvironment(): void
    {
        $origins = $this->parseCommaList($this->readEnvString('CORS_ALLOWED_ORIGINS'));

        if ($origins !== []) {
            $this->assertProductionSafeOrigins($origins);
            $this->default['allowedOrigins'] = $origins;
        }

        $methods = $this->parseCommaList($this->readEnvString('CORS_ALLOWED_METHODS'));
        if ($methods !== []) {
            $this->default['allowedMethods'] = array_map('strtoupper', $methods);
        } elseif ($this->default['allowedMethods'] === []) {
            $this->default['allowedMethods'] = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        }

        $headers = $this->parseCommaList($this->readEnvString('CORS_ALLOWED_HEADERS'));
        if ($headers !== []) {
            $this->default['allowedHeaders'] = $headers;
        } elseif ($this->default['allowedHeaders'] === []) {
            $this->default['allowedHeaders'] = [
                'Authorization',
                'Content-Type',
                'X-Requested-With',
                'X-Request-Id',
                'Idempotency-Key',
            ];
        }

        $credRaw = $this->readEnvString('CORS_ALLOW_CREDENTIALS');
        if ($credRaw !== '') {
            $this->default['supportsCredentials'] = $this->parseBool($credRaw);
        }

        $maxAgeRaw = $this->readEnvString('CORS_MAX_AGE');
        if ($maxAgeRaw !== '' && ctype_digit($maxAgeRaw)) {
            $this->default['maxAge'] = max(0, (int) $maxAgeRaw);
        }
    }

    /**
     * @param list<string> $origins
     */
    private function assertProductionSafeOrigins(array $origins): void
    {
        if (ENVIRONMENT !== 'production') {
            return;
        }

        foreach ($origins as $origin) {
            if ($origin === '*') {
                throw new RuntimeException(
                    'CORS_ALLOWED_ORIGINS must not use "*" in production (especially with Authorization).',
                );
            }
        }
    }

    private function readEnvString(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @return list<string>
     */
    private function parseCommaList(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));

        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }

    private function parseBool(string $raw): bool
    {
        $v = strtolower(trim($raw));

        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
