<?php

namespace App\Filters;

use App\Exceptions\UnauthorizedException;
use Config\Database;
use App\Services\Auth\TokenService;
use App\Services\RateLimitKeyService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthTokenFilter implements FilterInterface
{
    public function __construct(
        private readonly RateLimitKeyService $rateLimitKeyService = new RateLimitKeyService(),
        private readonly TokenService $tokenService = new TokenService()
    ) {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = $request->getHeaderLine('Authorization');
        if (trim($auth) === '') {
            return api_response(service('response'), false, 'Kimlik dogrulama gerekli', null, [
                'error_code' => 'TOKEN_MISSING',
            ], 401);
        }

        if (! preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return api_response(service('response'), false, 'Token formati gecersiz', null, [
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        }

        try {
            $tokenContext = $this->tokenService->validateAccessToken(trim((string) $matches[1]));
        } catch (UnauthorizedException $e) {
            return api_response(service('response'), false, $e->getMessage(), null, [
                'error_code' => $e->getErrorCode(),
            ], 401);
        } catch (\Throwable $e) {
            return api_response(service('response'), false, 'Token gecersiz', null, [
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        }

        $userId = (int) ($tokenContext['user_id'] ?? 0);
        $companyId = (int) ($tokenContext['company_id'] ?? 0);
        if ($companyId <= 0 && $userId > 0) {
            $userRow = Database::connect()->table('users')
                ->select('company_id')
                ->where('id', $userId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();
            $companyId = (int) ($userRow['company_id'] ?? 0);
        }

        $request->user = (object) [
            'id' => $userId,
            'roles' => is_array($tokenContext['roles'] ?? null) ? $tokenContext['roles'] : [],
            'permissions' => is_array($tokenContext['permissions'] ?? null) ? $tokenContext['permissions'] : [],
        ];
        $request->roles = $tokenContext['roles'];
        $request->permissions = is_array($tokenContext['permissions'] ?? null) ? $tokenContext['permissions'] : [];
        $request->user_id = $userId;
        $request->company_id = $companyId;
        $request->session_id = $tokenContext['session_id'] ?? null;
        $request->rate_limit_key = $this->rateLimitKeyService->build();

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
