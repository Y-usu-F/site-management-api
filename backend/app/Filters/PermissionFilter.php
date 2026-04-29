<?php

namespace App\Filters;

use App\Services\Auth\AuthorizationService;
use App\Services\Auth\PermissionMatrixService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function __construct(
        private readonly PermissionMatrixService $permissionMatrixService = new PermissionMatrixService(),
        private readonly AuthorizationService $authorizationService = new AuthorizationService()
    ) {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) ($request->user?->id ?? 0);
        $companyId = (int) ($request->company_id ?? 0);

        if ($userId <= 0 || $companyId <= 0) {
            return api_response(service('response'), false, 'Kimlik dogrulama gerekli', null, [
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $permissionCode = $this->resolvePermissionCode($arguments);
        if ($permissionCode === '') {
            return api_response(service('response'), false, 'Permission parametresi gerekli', null, [
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $this->permissionMatrixService->assertPermissionKnown($permissionCode);

        $targetCompanyId = isset($request->target_company_id) ? (int) $request->target_company_id : null;
        $decision = $this->authorizationService->authorize($userId, $companyId, $permissionCode, $targetCompanyId);
        if (! $decision['allowed']) {
            return api_response(service('response'), false, 'Bu islem icin yetkiniz yok', null, [
                'error_code' => 'FORBIDDEN',
                'reason' => $decision['reason'],
                'permission' => $decision['permission'],
                'scope' => $decision['scope'],
            ], 403);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * @param array<int, mixed>|null $arguments
     */
    private function resolvePermissionCode(?array $arguments): string
    {
        if ($arguments === null || $arguments === []) {
            return '';
        }

        $permissionCode = is_string($arguments[0]) ? trim($arguments[0]) : '';
        return strtolower($permissionCode);
    }
}

