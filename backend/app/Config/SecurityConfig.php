<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SecurityConfig extends BaseConfig
{
    public bool $requireRequestId = true;
    public array $sensitiveFields = ['password', 'token', 'authorization'];
    public string $rateLimitKeyPattern = 'ip:user:endpoint';
    public bool $requireIdempotencyKeyForWrites = false;

    /**
     * Auth endpoint rate limit ayarlari.
     */
    public int $rateLimitLoginMaxAttempts = 5;
    public int $rateLimitLoginWindowSeconds = 60;
    public int $rateLimitForgotPasswordMaxAttempts = 5;
    public int $rateLimitForgotPasswordWindowSeconds = 300;
    public int $rateLimitResetPasswordMaxAttempts = 5;
    public int $rateLimitResetPasswordWindowSeconds = 300;

    /**
     * Password policy ayarlari.
     */
    public int $passwordMinLength = 8;
    public int $passwordMaxLength = 128;
    public bool $passwordRequireUppercase = true;
    public bool $passwordRequireLowercase = true;
    public bool $passwordRequireNumber = true;
    public bool $passwordRequireSpecialChar = true;

    public function __construct()
    {
        parent::__construct();

        $this->rateLimitKeyPattern = (string) env('security.rateLimitKeyPattern', $this->rateLimitKeyPattern);
        $this->rateLimitLoginMaxAttempts = (int) env('security.rateLimitLoginMaxAttempts', $this->rateLimitLoginMaxAttempts);
        $this->rateLimitLoginWindowSeconds = (int) env('security.rateLimitLoginWindowSeconds', $this->rateLimitLoginWindowSeconds);
        $this->rateLimitForgotPasswordMaxAttempts = (int) env('security.rateLimitForgotPasswordMaxAttempts', $this->rateLimitForgotPasswordMaxAttempts);
        $this->rateLimitForgotPasswordWindowSeconds = (int) env('security.rateLimitForgotPasswordWindowSeconds', $this->rateLimitForgotPasswordWindowSeconds);
        $this->rateLimitResetPasswordMaxAttempts = (int) env('security.rateLimitResetPasswordMaxAttempts', $this->rateLimitResetPasswordMaxAttempts);
        $this->rateLimitResetPasswordWindowSeconds = (int) env('security.rateLimitResetPasswordWindowSeconds', $this->rateLimitResetPasswordWindowSeconds);

        $this->passwordMinLength = (int) env('security.passwordMinLength', $this->passwordMinLength);
        $this->passwordMaxLength = (int) env('security.passwordMaxLength', $this->passwordMaxLength);
        $this->passwordRequireUppercase = filter_var(env('security.passwordRequireUppercase', $this->passwordRequireUppercase), FILTER_VALIDATE_BOOL);
        $this->passwordRequireLowercase = filter_var(env('security.passwordRequireLowercase', $this->passwordRequireLowercase), FILTER_VALIDATE_BOOL);
        $this->passwordRequireNumber = filter_var(env('security.passwordRequireNumber', $this->passwordRequireNumber), FILTER_VALIDATE_BOOL);
        $this->passwordRequireSpecialChar = filter_var(env('security.passwordRequireSpecialChar', $this->passwordRequireSpecialChar), FILTER_VALIDATE_BOOL);
    }
}
