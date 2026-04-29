<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) ($request->user?->id ?? 0);
        $companyId = (int) ($request->company_id ?? 0);
        $roles = is_array($request->roles ?? null) ? $request->roles : [];

        if ($userId <= 0 || $companyId <= 0) {
            return api_response(service('response'), false, 'Kimlik dogrulama gerekli', null, [
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        $requiredRoles = $this->normalizeRequiredRoles($arguments);
        if ($requiredRoles === []) {
            return null;
        }

        foreach ($requiredRoles as $requiredRole) {
            if (in_array($requiredRole, $roles, true)) {
                return null;
            }
        }

        return api_response(service('response'), false, 'Bu islem icin yetkiniz yok', null, [
            'error_code' => 'FORBIDDEN',
        ], 403);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * @param array<int, mixed>|null $arguments
     * @return list<string>
     */
    private function normalizeRequiredRoles(?array $arguments): array
    {
        if ($arguments === null) {
            return [];
        }

        $roles = [];
        foreach ($arguments as $argument) {
            if (! is_string($argument)) {
                continue;
            }

            foreach (explode(',', $argument) as $item) {
                $normalized = strtolower(trim($item));
                if ($normalized !== '') {
                    $roles[] = $normalized;
                }
            }
        }

        return array_values(array_unique($roles));
    }
}

