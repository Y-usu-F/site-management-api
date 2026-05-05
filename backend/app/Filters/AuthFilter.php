<?php

namespace App\Filters;

use App\Exceptions\UnauthorizedException;
use App\Services\Auth\TokenService;
use App\Services\RateLimitKeyService;
use App\Support\RequestRuntime;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

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

        RequestRuntime::setAuthContext([
            'user_id' => (int) ($tokenContext['user_id'] ?? 0),
            'company_id' => (int) ($tokenContext['company_id'] ?? 0),
            'roles' => is_array($tokenContext['roles'] ?? null) ? $tokenContext['roles'] : [],
            'permissions' => is_array($tokenContext['permissions'] ?? null) ? $tokenContext['permissions'] : [],
            'session_id' => $tokenContext['session_id'] ?? null,
        ]);
        $this->setLegacyRequestAuthContext($request, $tokenContext);
        RequestRuntime::setRateLimitKey($this->rateLimitKeyService->build());
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * @param array<string,mixed> $tokenContext
     */
    private function setLegacyRequestAuthContext(RequestInterface $request, array $tokenContext): void
    {
        $assign = static function () use ($request, $tokenContext): void {
            $request->user = (object) ['id' => $tokenContext['user_id']];
            $request->roles = $tokenContext['roles'];
            $request->company_id = $tokenContext['company_id'];
            $request->session_id = $tokenContext['session_id'] ?? null;
        };

        if (! (defined('ENVIRONMENT') && ENVIRONMENT === 'testing')) {
            $assign();
            return;
        }

        $previous = error_reporting();
        error_reporting($previous & ~E_DEPRECATED);
        $previousHandler = set_error_handler(static function (int $severity, string $message): bool {
            return $severity === E_DEPRECATED
                && str_contains($message, 'Creation of dynamic property CodeIgniter\\HTTP\\IncomingRequest::$');
        });
        try {
            $assign();
        } finally {
            restore_error_handler();
            error_reporting($previous);
        }
    }
}
