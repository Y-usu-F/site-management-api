<?php

namespace App\Filters;

use App\Exceptions\UnauthorizedException;
use Config\Database;
use App\Services\Auth\TokenService;
use App\Services\RateLimitKeyService;
use App\Support\RequestRuntime;
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

        RequestRuntime::setAuthContext([
            'user_id' => $userId,
            'company_id' => $companyId,
            'roles' => is_array($tokenContext['roles'] ?? null) ? $tokenContext['roles'] : [],
            'permissions' => is_array($tokenContext['permissions'] ?? null) ? $tokenContext['permissions'] : [],
            'session_id' => $tokenContext['session_id'] ?? null,
        ]);
        $this->setLegacyRequestAuthContext($request, $tokenContext, $userId, $companyId);
        RequestRuntime::setRateLimitKey($this->rateLimitKeyService->build());

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * @param array<string,mixed> $tokenContext
     */
    private function setLegacyRequestAuthContext(RequestInterface $request, array $tokenContext, int $userId, int $companyId): void
    {
        if (! (defined('ENVIRONMENT') && ENVIRONMENT === 'testing')) {
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
            return;
        }

        $previous = error_reporting();
        error_reporting($previous & ~E_DEPRECATED);
        $previousHandler = set_error_handler(static function (int $severity, string $message): bool {
            return $severity === E_DEPRECATED
                && str_contains($message, 'Creation of dynamic property CodeIgniter\\HTTP\\IncomingRequest::$');
        });
        try {
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
        } finally {
            restore_error_handler();
            error_reporting($previous);
        }
    }
}
