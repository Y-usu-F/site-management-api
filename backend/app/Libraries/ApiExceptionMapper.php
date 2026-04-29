<?php

namespace App\Libraries;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationApiException;
use Config\ErrorCatalog;
use Throwable;

final class ApiExceptionMapper
{
    private ErrorCatalog $errorCatalog;

    public function __construct(?ErrorCatalog $errorCatalog = null)
    {
        $this->errorCatalog = $errorCatalog ?? new ErrorCatalog();
    }

    /**
     * @return array{status:int,message:string,error_code:string,details:array<string,mixed>|null}
     */
    public function map(Throwable $exception): array
    {
        if ($exception instanceof ApiException) {
            $details = null;
            if ($exception instanceof ValidationApiException && $exception->getErrors() !== []) {
                $details = $exception->getErrors();
            }

            return [
                'status' => $exception->getHttpCode(),
                'message' => $exception->getMessage(),
                'error_code' => $exception->getErrorCode(),
                'details' => $details,
            ];
        }

        foreach ($this->errorCatalog->exceptionMap as $className => $mapping) {
            if ($exception instanceof $className) {
                return [
                    'status' => $mapping['http_status'],
                    'message' => $exception->getMessage(),
                    'error_code' => $mapping['error_code'],
                    'details' => null,
                ];
            }
        }

        return [
            'status' => $this->errorCatalog->fallbackHttpStatus,
            'message' => strtolower((string) env('APP_ENV', 'production')) === 'production'
                ? 'Beklenmeyen bir hata olustu'
                : $exception->getMessage(),
            'error_code' => $this->errorCatalog->fallbackErrorCode,
            'details' => null,
        ];
    }
}
