<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthConfig extends BaseConfig
{
    public string $jwtIssuer = 'lms-api';
    public string $jwtAudience = 'lms-clients';

    /**
     * Access token omru (saniye). Faz 04: kisa omurlu token.
     */
    public int $accessTokenTtl = 900;

    /**
     * Refresh token omru (saniye). Varsayilan: 14 gun.
     */
    public int $refreshTokenTtl = 1209600;

    public int $idempotencyTtl = 86400;
    public string $jwtSecret = '';

    public string $refreshCookieName = 'refresh_token';
    public string $refreshCookiePath = '/';
    public string $refreshCookieDomain = '';
    public bool $refreshCookieHttpOnly = true;
    public string $refreshCookieSameSite = 'Lax';
    public bool $refreshCookieSecure = false;
    public bool $permissionCacheEnabled = true;
    public int $permissionCacheTtl = 300;

    public function __construct()
    {
        parent::__construct();

        $this->jwtIssuer = (string) $this->envFirst(['JWT_ISSUER', 'auth.jwtIssuer'], $this->jwtIssuer);
        $this->jwtAudience = (string) $this->envFirst(['JWT_AUDIENCE', 'auth.jwtAudience'], $this->jwtAudience);
        $this->accessTokenTtl = (int) $this->envFirst(['JWT_ACCESS_TTL', 'auth.jwtAccessTtl'], $this->accessTokenTtl);
        $this->refreshTokenTtl = (int) $this->envFirst(['JWT_REFRESH_TTL', 'auth.jwtRefreshTtl'], $this->refreshTokenTtl);
        $this->jwtSecret = (string) $this->envFirst(['JWT_SECRET', 'auth.jwtSecret'], $this->jwtSecret);

        $this->refreshCookieName = (string) env('auth.refreshCookieName', $this->refreshCookieName);
        $this->refreshCookiePath = (string) env('auth.refreshCookiePath', $this->refreshCookiePath);
        $this->refreshCookieDomain = (string) env('auth.refreshCookieDomain', $this->refreshCookieDomain);
        $this->refreshCookieHttpOnly = filter_var(
            env('auth.refreshCookieHttpOnly', $this->refreshCookieHttpOnly),
            FILTER_VALIDATE_BOOL
        );
        $this->refreshCookieSameSite = (string) env('auth.refreshCookieSameSite', $this->refreshCookieSameSite);

        $secureOverride = env('auth.refreshCookieSecure');
        if ($secureOverride !== null) {
            $this->refreshCookieSecure = filter_var($secureOverride, FILTER_VALIDATE_BOOL);
        } else {
            $this->refreshCookieSecure = ENVIRONMENT === 'production';
        }

        $this->permissionCacheEnabled = filter_var(
            env('auth.permissionCacheEnabled', $this->permissionCacheEnabled),
            FILTER_VALIDATE_BOOL
        );
        $this->permissionCacheTtl = (int) env('auth.permissionCacheTtl', $this->permissionCacheTtl);
    }

    private function envFirst(array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = env($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}
