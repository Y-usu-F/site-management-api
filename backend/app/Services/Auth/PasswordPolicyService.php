<?php

namespace App\Services\Auth;

use App\Exceptions\ValidationApiException;
use Config\SecurityConfig;

class PasswordPolicyService
{
    public function __construct(
        private readonly SecurityConfig $securityConfig = new SecurityConfig()
    ) {
    }

    public function maxFailedLoginAttempts(): int
    {
        // Rate limit penceresi (ornegin 5) ile ayni sayiya kilitlemek,
        // 6. istekte rate-limit filtresinden once hesap kilidine takilmasina yol acabiliyor.
        // Kilit esigi, route rate limit esiginden buyuk tutulur.
        return max(1, (int) $this->securityConfig->rateLimitLoginMaxAttempts + 1);
    }

    public function lockDurationSeconds(): int
    {
        return max(60, (int) $this->securityConfig->rateLimitLoginWindowSeconds);
    }

    public function isLocked(?string $lockedUntil): bool
    {
        if ($lockedUntil === null || trim($lockedUntil) === '') {
            return false;
        }

        return strtotime($lockedUntil) > time();
    }

    public function validateNewPassword(string $newPassword): void
    {
        $errors = [];
        $length = strlen($newPassword);

        if ($length < $this->securityConfig->passwordMinLength || $length > $this->securityConfig->passwordMaxLength) {
            $errors['new_password'][] = 'Sifre uzunlugu gecersiz';
        }

        if ($this->securityConfig->passwordRequireUppercase && ! preg_match('/[A-Z]/', $newPassword)) {
            $errors['new_password'][] = 'En az bir buyuk harf gerekli';
        }

        if ($this->securityConfig->passwordRequireLowercase && ! preg_match('/[a-z]/', $newPassword)) {
            $errors['new_password'][] = 'En az bir kucuk harf gerekli';
        }

        if ($this->securityConfig->passwordRequireNumber && ! preg_match('/\d/', $newPassword)) {
            $errors['new_password'][] = 'En az bir rakam gerekli';
        }

        if ($this->securityConfig->passwordRequireSpecialChar && ! preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $errors['new_password'][] = 'En az bir ozel karakter gerekli';
        }

        if ($errors !== []) {
            throw new ValidationApiException('Yeni sifre policy kurallarina uymuyor', $errors);
        }
    }
}
