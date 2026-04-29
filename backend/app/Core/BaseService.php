<?php

namespace App\Core;

use App\Services\Common\AuditLogService;

abstract class BaseService
{
    public function __construct(
        protected readonly AuditLogService $auditLogService = new AuditLogService()
    ) {
    }

    protected function audit(string $action, array $payload = []): void
    {
        $this->auditLogService->recordEvent($action, $payload);
    }
}
