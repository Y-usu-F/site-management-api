<?php

namespace App\Core;

use App\Libraries\ApiExceptionMapper;
use App\Exceptions\UnauthorizedException;
use App\Support\RequestRuntime;
use Config\ErrorCatalog;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

abstract class ApiController extends ResourceController
{
    protected RequestContext $context;
    protected ApiValidator $apiValidator;
    protected ErrorCatalog $errorCatalog;
    protected ApiExceptionMapper $exceptionMapper;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $path = $request instanceof IncomingRequest ? trim($request->getPath(), '/') : '';
        $requestContext = RequestRuntime::getRequestContext();
        $timestamp = is_array($requestContext)
            ? (string) ($requestContext['timestamp'] ?? gmdate('c'))
            : gmdate('c');

        $this->context = new RequestContext(
            RequestRuntime::getRequestId() ?: (string) $request->getHeaderLine('X-Request-Id'),
            RequestRuntime::getUserId() > 0 ? RequestRuntime::getUserId() : null,
            RequestRuntime::getCompanyId() > 0 ? RequestRuntime::getCompanyId() : null,
            $request->getIPAddress(),
            $request->getUserAgent()->getAgentString(),
            strtoupper($request->getMethod()),
            $path,
            $timestamp
        );
        $this->apiValidator = new ApiValidator();
        $this->errorCatalog = new ErrorCatalog();
        $this->exceptionMapper = new ApiExceptionMapper($this->errorCatalog);
    }

    protected function ok(string $message, mixed $data = null)
    {
        return api_response($this->response, true, $message, $data, null, 200);
    }

    protected function failApi(string $message, mixed $errors, int $statusCode)
    {
        return api_response($this->response, false, $message, null, $errors, $statusCode);
    }

    protected function failFromException(Throwable $exception)
    {
        $mapped = $this->exceptionMapper->map($exception);
        $errors = ['error_code' => $mapped['error_code']];
        if ($mapped['details'] !== null) {
            $errors['details'] = $mapped['details'];
        }

        $logContext = [
            'class' => $exception::class,
            'status' => $mapped['status'],
            'error_code' => $mapped['error_code'],
            'request_id' => $this->context->requestId ?: null,
        ];
        $isExpectedUnauthorizedInTesting = defined('ENVIRONMENT')
            && ENVIRONMENT === 'testing'
            && $exception instanceof UnauthorizedException
            && in_array($mapped['error_code'], ['UNAUTHORIZED', 'TOKEN_INVALID', 'TOKEN_REUSED', 'TOKEN_EXPIRED', 'TOKEN_ALREADY_USED', 'USER_INACTIVE'], true);
        $logLevel = $mapped['status'] >= 500
            ? 'error'
            : ($isExpectedUnauthorizedInTesting ? 'debug' : 'warning');

        log_message(
            $logLevel,
            'API exception: {class} [{error_code}] {message}',
            array_merge($logContext, ['message' => $exception->getMessage()])
        );

        return $this->failApi($mapped['message'], $errors, $mapped['status']);
    }
}
