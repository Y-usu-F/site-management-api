<?php

namespace App\Libraries;

use App\Services\Common\AuditLogService;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions;
use Throwable;

final class ApiExceptionHandler implements ExceptionHandlerInterface
{
    private ApiExceptionMapper $mapper;
    private AuditLogService $auditLogService;
    private ExceptionHandler $defaultHandler;

    public function __construct(Exceptions $config)
    {
        $this->defaultHandler = new ExceptionHandler($config);
        $this->mapper = new ApiExceptionMapper();
        $this->auditLogService = new AuditLogService();
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode
    ): void {
        if (! $this->isApiRequest($request)) {
            $this->defaultHandler->handle($exception, $request, $response, $statusCode, $exitCode);
            return;
        }

        $mapped = $this->mapper->map($exception);
        $requestId = (string) ($request->request_id ?? $request->getHeaderLine('X-Request-Id'));
        $errors = ['error_code' => $mapped['error_code']];
        if ($mapped['details'] !== null) {
            $errors['details'] = $mapped['details'];
        }

        log_message(
            $mapped['status'] >= 500 ? 'error' : 'warning',
            'API exception handled globally: {class} [{error_code}] {message}',
            [
                'class' => $exception::class,
                'error_code' => $mapped['error_code'],
                'message' => $exception->getMessage(),
                'request_id' => $requestId !== '' ? $requestId : null,
                'status' => $mapped['status'],
            ]
        );

        $this->writeSecurityAuditIfNeeded($mapped['status'], $mapped['error_code'], $request, $exception);

        $response
            ->setStatusCode($mapped['status'])
            ->setJSON([
                'success' => false,
                'message' => $mapped['message'],
                'data' => null,
                'errors' => $errors,
                'meta' => [
                    'request_id' => $requestId !== '' ? $requestId : null,
                ],
            ])
            ->send();

        exit($exitCode);
    }

    private function isApiRequest(RequestInterface $request): bool
    {
        if (method_exists($request, 'getPath')) {
            $path = trim((string) $request->getPath(), '/');
            return str_starts_with($path, 'api/v1');
        }

        return false;
    }

    private function writeSecurityAuditIfNeeded(int $status, string $errorCode, RequestInterface $request, Throwable $exception): void
    {
        if (! in_array($status, [401, 403], true)) {
            return;
        }

        $event = $errorCode === 'TENANT_FORBIDDEN'
            ? 'security.tenant.violation'
            : 'security.access.forbidden';

        try {
            $this->auditLogService->recordEvent($event, [
                'status' => 'failed',
                'company_id' => $request->company_id ?? null,
                'actor_user_id' => $request->user?->id ?? null,
                'action' => $event,
                'entity_type' => 'security',
                'entity_id' => null,
                'old_values' => [],
                'new_values' => [],
                'request_id' => (string) ($request->request_id ?? $request->getHeaderLine('X-Request-Id')),
                'meta' => [
                    'http_status' => $status,
                    'error_code' => $errorCode,
                    'exception' => $exception::class,
                ],
            ]);
        } catch (Throwable) {
            // Audit failure must never break global exception response flow.
        }
    }
}
