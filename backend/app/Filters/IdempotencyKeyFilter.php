<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AuthConfig;
use Config\SecurityConfig;

class IdempotencyKeyFilter implements FilterInterface
{
    public function __construct(
        private readonly AuthConfig $authConfig = new AuthConfig(),
        private readonly SecurityConfig $securityConfig = new SecurityConfig()
    ) {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $key = trim($request->getHeaderLine('Idempotency-Key'));
        if ($key === '' && $this->securityConfig->requireIdempotencyKeyForWrites) {
            return api_response(service('response'), false, 'Idempotency-Key zorunlu', null, [
                'error_code' => 'MISSING_IDEMPOTENCY_KEY',
            ], 422);
        }

        if ($key === '') {
            return null;
        }

        if (strlen($key) > 128) {
            return api_response(service('response'), false, 'Idempotency-Key gecersiz', null, [
                'error_code' => 'INVALID_IDEMPOTENCY_KEY',
            ], 422);
        }

        $hash = hash('sha256', $key . '|' . strtoupper($request->getMethod()) . '|' . $request->getPath());
        $request->idempotency_key_hash = $hash;

        $request->idempotency_ttl = $this->authConfig->idempotencyTtl;

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $key = trim($request->getHeaderLine('Idempotency-Key'));
        if ($key !== '') {
            $response->setHeader('Idempotency-Key', $key);
        }
    }
}
