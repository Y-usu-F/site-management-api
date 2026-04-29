<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class ApiResponseFormatFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $this->isApiV1Request($request)) {
            return $response;
        }

        if ($response instanceof DownloadResponse || $response->hasHeader('Content-Disposition')) {
            return $response;
        }

        $requestId = (string) ($request->request_id ?? $_SERVER['HTTP_X_REQUEST_ID'] ?? '');
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = $this->decodeJsonBody($body);

        if (is_array($decoded) && $this->isEnvelope($decoded)) {
            $decoded['meta'] = is_array($decoded['meta'] ?? null) ? $decoded['meta'] : [];
            $decoded['meta']['request_id'] = $decoded['meta']['request_id'] ?? $requestId ?: null;
            return $response->setJSON($decoded);
        }

        $wrapped = $this->buildEnvelope($decoded, $body, $statusCode, $requestId);
        return $response->setJSON($wrapped);
    }

    private function isApiV1Request(RequestInterface $request): bool
    {
        if ($request instanceof IncomingRequest) {
            return str_starts_with(trim($request->getPath(), '/'), 'api/v1');
        }

        $uri = trim((string) ($_SERVER['REQUEST_URI'] ?? ''), '/');
        return str_starts_with($uri, 'api/v1');
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decodeJsonBody(string $body): ?array
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return null;
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string,mixed>|null $decoded
     * @return array<string,mixed>
     */
    private function buildEnvelope(?array $decoded, string $rawBody, int $statusCode, string $requestId): array
    {
        $success = $statusCode >= 200 && $statusCode < 400;
        $defaultMessage = $success ? 'Islem basarili' : 'Islem basarisiz';

        $message = $defaultMessage;
        $data = $success ? null : null;
        $errors = $success ? null : [];
        $meta = ['request_id' => $requestId !== '' ? $requestId : null];

        if (is_array($decoded)) {
            $message = isset($decoded['message']) && is_string($decoded['message']) ? $decoded['message'] : $defaultMessage;
            $data = $decoded['data'] ?? ($success ? $decoded : null);
            $errors = $decoded['errors'] ?? (! $success ? ($decoded['error'] ?? $decoded) : null);
            if (isset($decoded['meta']) && is_array($decoded['meta'])) {
                $meta = array_merge($decoded['meta'], $meta);
            }
        } elseif (trim($rawBody) !== '') {
            if ($success) {
                $data = ['raw' => $rawBody];
            } else {
                $errors = ['raw' => $rawBody];
            }
        }

        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => $meta,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function isEnvelope(array $payload): bool
    {
        return array_key_exists('success', $payload)
            && array_key_exists('message', $payload)
            && array_key_exists('data', $payload)
            && array_key_exists('errors', $payload)
            && array_key_exists('meta', $payload);
    }
}
