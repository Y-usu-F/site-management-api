<?php

namespace App\Libraries\Auth;

use Config\AuthConfig;
use RuntimeException;
use UnexpectedValueException;

class JwtManager
{
    private AuthConfig $authConfig;

    public function __construct(?AuthConfig $authConfig = null)
    {
        $this->authConfig = $authConfig ?? new AuthConfig();
    }

    public function issue(array $claims, int $ttl): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttl,
            'iss' => $this->authConfig->jwtIssuer,
            'aud' => $this->authConfig->jwtAudience,
            'jti' => bin2hex(random_bytes(16)),
        ]);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $headerEncoded = $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->sign($headerEncoded . '.' . $payloadEncoded);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    public function decodeAndValidate(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        $headerJson = $this->base64UrlDecode($headerEncoded);
        $payloadJson = $this->base64UrlDecode($payloadEncoded);
        if ($headerJson === false || $payloadJson === false) {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (! is_array($header) || ! is_array($payload)) {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        $expected = $this->sign($headerEncoded . '.' . $payloadEncoded);
        if (! hash_equals($expected, $signatureEncoded)) {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        $now = time();
        if (! isset($payload['exp']) || (int) $payload['exp'] <= $now) {
            throw new UnexpectedValueException('TOKEN_EXPIRED');
        }

        if (($payload['iss'] ?? '') !== $this->authConfig->jwtIssuer) {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        if (($payload['aud'] ?? '') !== $this->authConfig->jwtAudience) {
            throw new UnexpectedValueException('TOKEN_INVALID');
        }

        return $payload;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(
            hash_hmac('sha256', $data, $this->getSigningKey(), true)
        );
    }

    private function getSigningKey(): string
    {
        $secret = trim($this->authConfig->jwtSecret);
        if ($secret === '') {
            throw new RuntimeException('JWT secret tanimli degil. JWT_SECRET env degeri zorunludur.');
        }

        return $secret;
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $input): string|false
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), true);
    }
}
