<?php

namespace Tests\Unit\Common;

use App\Exceptions\ConflictApiException;
use App\Exceptions\NotFoundApiException;
use App\Exceptions\TenantAccessDeniedException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationApiException;
use App\Libraries\ApiExceptionMapper;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class ApiExceptionMapperTest extends CIUnitTestCase
{
    public function testValidationException422VeDetailsDoner(): void
    {
        $mapper = new ApiExceptionMapper();
        $mapped = $mapper->map(new ValidationApiException('Validation failed', ['email' => 'required']));

        $this->assertSame(422, $mapped['status']);
        $this->assertSame('VALIDATION_ERROR', $mapped['error_code']);
        $this->assertSame(['email' => 'required'], $mapped['details']);
    }

    public function testAuth401Doner(): void
    {
        $mapper = new ApiExceptionMapper();
        $mapped = $mapper->map(new UnauthorizedException('Unauthorized'));

        $this->assertSame(401, $mapped['status']);
        $this->assertSame('UNAUTHORIZED', $mapped['error_code']);
    }

    public function testPermissionTenant403Doner(): void
    {
        $mapper = new ApiExceptionMapper();
        $mapped = $mapper->map(new TenantAccessDeniedException());

        $this->assertSame(403, $mapped['status']);
        $this->assertSame('FORBIDDEN', $mapped['error_code']);
    }

    public function testNotFound404Doner(): void
    {
        $mapper = new ApiExceptionMapper();
        $mapped = $mapper->map(new NotFoundApiException('Missing'));

        $this->assertSame(404, $mapped['status']);
        $this->assertSame('NOT_FOUND', $mapped['error_code']);
    }

    public function testConflict409Doner(): void
    {
        $mapper = new ApiExceptionMapper();
        $mapped = $mapper->map(new ConflictApiException('Conflict'));

        $this->assertSame(409, $mapped['status']);
        $this->assertSame('CONFLICT', $mapped['error_code']);
    }

    public function testUnexpectedFallback500Doner(): void
    {
        $mapper = new ApiExceptionMapper();
        $mapped = $mapper->map(new RuntimeException('Unexpected'));

        $this->assertSame(500, $mapped['status']);
        $this->assertSame('INTERNAL_ERROR', $mapped['error_code']);
        $this->assertIsString($mapped['message']);
    }
}
