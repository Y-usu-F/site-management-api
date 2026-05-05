<?php

namespace App\Filters;

use App\Support\RequestRuntime;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RequestIdFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $incoming = trim($request->getHeaderLine('X-Request-Id'));
        $requestId = $this->isValidRequestId($incoming) ? $incoming : $this->generateRequestId();

        RequestRuntime::setRequestId($requestId);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $requestId = RequestRuntime::getRequestId();
        if ($requestId !== '') {
            $response->setHeader('X-Request-Id', $requestId);
        }
    }

    private function isValidRequestId(string $requestId): bool
    {
        if ($requestId === '' || strlen($requestId) > 128) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $requestId) === 1;
    }

    private function generateRequestId(): string
    {
        return 'req_' . bin2hex(random_bytes(8));
    }
}
