<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Auth\AuthService;
use App\Validation\AuthValidation;
use Throwable;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService = new AuthService()
    )
    {
    }

    public function login()
    {
        try {
            $payload = $this->apiValidator->validateOrFail(
                $this->request->getJSON(true) ?? [],
                AuthValidation::loginRules()
            );
            $result = $this->authService->login(
                (string) $payload['email'],
                (string) $payload['password']
            );

            $this->applyCookieHints($result);

            return $this->ok('Giris basarili', $result);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function me()
    {
        try {
            return $this->ok('Profil getirildi', $this->authService->me());
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function refresh()
    {
        try {
            $payload = $this->apiValidator->validateOrFail(
                $this->request->getJSON(true) ?? [],
                AuthValidation::refreshRules()
            );

            $result = $this->authService->refresh((string) $payload['refresh_token']);
            $this->applyCookieHints($result);

            return $this->ok('Token yenilendi', $result);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function logout()
    {
        try {
            $result = $this->authService->logout();
            $this->applyCookieHints($result);
            return $this->ok('Cikis yapildi', $result);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function forgotPassword()
    {
        try {
            $payload = $this->apiValidator->validateOrFail(
                $this->request->getJSON(true) ?? [],
                AuthValidation::forgotPasswordRules()
            );
            $result = $this->authService->forgotPassword((string) $payload['email']);
            return $this->ok('Eger hesap varsa sifre yenileme talimati gonderilecektir', $result);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function resetPassword()
    {
        try {
            $payload = $this->apiValidator->validateOrFail(
                $this->request->getJSON(true) ?? [],
                AuthValidation::resetPasswordRules()
            );
            $result = $this->authService->resetPassword(
                (string) $payload['token'],
                (string) $payload['password']
            );
            return $this->ok('Sifre basariyla sifirlandi', $result);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    /**
     * @param array<string, mixed> $serviceResult
     */
    private function applyCookieHints(array $serviceResult): void
    {
        if (! isset($serviceResult['cookies']) || ! is_array($serviceResult['cookies'])) {
            return;
        }

        foreach ($serviceResult['cookies'] as $cookie) {
            if (! is_array($cookie) || ! isset($cookie['name'])) {
                continue;
            }

            $this->response->setCookie(
                (string) $cookie['name'],
                (string) ($cookie['value'] ?? ''),
                (int) ($cookie['expire'] ?? 0),
                (string) ($cookie['path'] ?? '/'),
                (string) ($cookie['domain'] ?? ''),
                (bool) ($cookie['secure'] ?? false),
                (bool) ($cookie['httponly'] ?? true),
                (string) ($cookie['samesite'] ?? 'Lax')
            );
        }
    }
}
