<?php

use CodeIgniter\HTTP\ResponseInterface;

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('api_response')) {
    /**
     * Tum AJAX okuma islemlerinde JSON data donulmesi icin standart format.
     */
    function api_response(
        ResponseInterface $response,
        bool $success,
        string $message,
        mixed $data = null,
        mixed $errors = null,
        int $statusCode = 200
    ): ResponseInterface {
        $request = service('request');
        $requestId = (string) ($request->request_id ?? $request->getHeaderLine('X-Request-Id'));

        return $response->setStatusCode($statusCode)->setJSON([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => [
                'request_id' => $requestId ?: null,
            ],
        ]);
    }
}

if (! function_exists('mask_sensitive_data')) {
    /**
     * @param array<string, mixed> $payload
     * @param list<string> $sensitiveFields
     * @return array<string, mixed>
     */
    function mask_sensitive_data(array $payload, array $sensitiveFields = ['password', 'token', 'authorization', 'secret']): array
    {
        $normalized = array_map(static fn ($field) => strtolower($field), $sensitiveFields);

        $masker = static function (mixed $value, string|int|null $key = null) use (&$masker, $normalized): mixed {
            if (is_array($value)) {
                $result = [];
                foreach ($value as $childKey => $childValue) {
                    $result[$childKey] = $masker($childValue, $childKey);
                }

                return $result;
            }

            if (is_string($key) && in_array(strtolower($key), $normalized, true)) {
                return '***';
            }

            return $value;
        };

        return $masker($payload);
    }
}
