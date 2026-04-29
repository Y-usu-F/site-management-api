<?php

namespace App\Core;

use App\Libraries\ApiExceptionMapper;
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
        $timestamp = is_array($request->request_context ?? null)
            ? (string) ($request->request_context['timestamp'] ?? gmdate('c'))
            : gmdate('c');

        $this->context = new RequestContext(
            (string) ($request->request_id ?? $request->getHeaderLine('X-Request-Id')),
            $request->user?->id ?? null,
            $request->company_id ?? null,
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
        log_message(
            $mapped['status'] >= 500 ? 'error' : 'warning',
            'API exception: {class} [{error_code}] {message}',
            array_merge($logContext, ['message' => $exception->getMessage()])
        );

        return $this->failApi($mapped['message'], $errors, $mapped['status']);
    }
}
