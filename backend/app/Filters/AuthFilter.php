<?php

namespace App\Filters;

use App\Exceptions\UnauthorizedException;
use App\Services\Auth\TokenService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RateLimitKeyService;

class AuthFilter implements FilterInterface
{
    public function __construct(
        private readonly RateLimitKeyService $rateLimitKeyService = new RateLimitKeyService(),
        private readonly TokenService $tokenService = new TokenService()
    )
    {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = $request->getHeaderLine('Authorization');
        if ($auth === '') {
            return api_response(service('response'), false, 'Kimlik dogrulama gerekli', null, [
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        if (! preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return api_response(service('response'), false, 'Token formati gecersiz', null, [
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        try {
            $tokenContext = $this->tokenService->validateAccessToken(trim((string) $matches[1]));
        } catch (UnauthorizedException $e) {
            return api_response(service('response'), false, $e->getMessage(), null, [
                'error_code' => $e->getErrorCode(),
            ], 401);
        } catch (\Throwable $e) {
            return api_response(service('response'), false, 'Kimlik dogrulama gerekli', null, [
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $request->user = (object) ['id' => $tokenContext['user_id']];
        $request->roles = $tokenContext['roles'];
        $request->company_id = $tokenContext['company_id'];
        $request->session_id = $tokenContext['session_id'] ?? null;
        $request->rate_limit_key = $this->rateLimitKeyService->build();
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
