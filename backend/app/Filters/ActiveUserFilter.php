<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ActiveUserFilter implements FilterInterface
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel()
    ) {
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) ($request->user?->id ?? 0);
        if ($userId <= 0) {
            return api_response(service('response'), false, 'Kimlik dogrulama gerekli', null, [
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        }

        $user = $this->userModel->find($userId);
        if (! is_array($user)) {
            return api_response(service('response'), false, 'Kullanici bulunamadi', null, [
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        }

        $status = strtolower((string) ($user['status'] ?? 'active'));
        $isActive = isset($user['is_active']) ? (int) $user['is_active'] === 1 : true;
        if ($status !== 'active' || ! $isActive) {
            return api_response(service('response'), false, 'Kullanici aktif degil', null, [
                'error_code' => 'USER_INACTIVE',
            ], 403);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
