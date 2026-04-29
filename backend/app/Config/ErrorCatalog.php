<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ErrorCatalog extends BaseConfig
{
    /**
     * @var array<string, array{http_status:int,error_code:string}>
     */
    public array $exceptionMap = [
        \App\Exceptions\ValidationApiException::class => ['http_status' => 422, 'error_code' => 'VALIDATION_ERROR'],
        \App\Exceptions\UnauthorizedException::class => ['http_status' => 401, 'error_code' => 'UNAUTHORIZED'],
        \App\Exceptions\ForbiddenException::class => ['http_status' => 403, 'error_code' => 'FORBIDDEN'],
        \App\Exceptions\TenantAccessDeniedException::class => ['http_status' => 403, 'error_code' => 'TENANT_FORBIDDEN'],
        \App\Exceptions\AuthorizationException::class => ['http_status' => 403, 'error_code' => 'FORBIDDEN'],
        \App\Exceptions\PermissionNotFoundException::class => ['http_status' => 403, 'error_code' => 'PERMISSION_NOT_FOUND'],
        \App\Exceptions\InvalidPermissionCodeException::class => ['http_status' => 403, 'error_code' => 'INVALID_PERMISSION_CODE'],
        \App\Exceptions\NotFoundApiException::class => ['http_status' => 404, 'error_code' => 'NOT_FOUND'],
        \App\Exceptions\ConflictApiException::class => ['http_status' => 409, 'error_code' => 'CONFLICT'],
        \DomainException::class => ['http_status' => 409, 'error_code' => 'CONFLICT'],
    ];

    public int $fallbackHttpStatus = 500;
    public string $fallbackErrorCode = 'INTERNAL_ERROR';
}
