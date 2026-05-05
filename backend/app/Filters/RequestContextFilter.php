<?php

namespace App\Filters;

use App\Services\RateLimitKeyService;
use App\Support\RequestRuntime;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RequestContextFilter implements FilterInterface
{
    public function __construct(private readonly RateLimitKeyService $rateLimitKeyService = new RateLimitKeyService())
    {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        RequestRuntime::clearAuthContext();
        $requestId = RequestRuntime::getRequestId();
        RequestRuntime::setRateLimitKey($this->rateLimitKeyService->build());

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

        RequestRuntime::setRequestContext($context);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $rateLimitKey = RequestRuntime::getRateLimitKey();
        if ($rateLimitKey !== '') {
            $response->setHeader('X-RateLimit-Key', $rateLimitKey);
        }
    }
}
