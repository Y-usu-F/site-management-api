<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RateLimitKeyService;

class RequestContextFilter implements FilterInterface
{
    public function __construct(private readonly RateLimitKeyService $rateLimitKeyService = new RateLimitKeyService())
    {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $requestId = (string) ($request->request_id ?? $_SERVER['HTTP_X_REQUEST_ID'] ?? '');
        $request->rate_limit_key = $this->rateLimitKeyService->build();

        $method = strtoupper($request->getMethod());
        $path = $request instanceof IncomingRequest ? trim($request->getPath(), '/') : '';
        $userAgent = $request instanceof IncomingRequest ? (string) $request->getUserAgent()->getAgentString() : '';

        $context = [
            'request_id' => $requestId,
            'ip' => (string) $request->getIPAddress(),
            'user_agent' => $userAgent,
            'method' => $method,
            'path' => $path,
            'timestamp' => gmdate('c'),
        ];

        $request->request_context = $context;
        $_SERVER['APP_REQUEST_CONTEXT'] = json_encode($context);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (isset($request->rate_limit_key)) {
            $response->setHeader('X-RateLimit-Key', (string) $request->rate_limit_key);
        }
    }
}
