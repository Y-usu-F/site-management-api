<?php

namespace Tests\Unit\Filters;

use App\Exceptions\PermissionNotFoundException;
use App\Filters\PermissionFilter;
use App\Services\Auth\AuthorizationService;
use App\Services\Auth\PermissionMatrixService;
use App\Support\RequestRuntime;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;

final class PermissionFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        RequestRuntime::clearAuthContext();
        parent::tearDown();
    }

    public function testContextYoksa401Doner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $filter = new PermissionFilter(
            $this->createMock(PermissionMatrixService::class),
            $this->createMock(AuthorizationService::class)
        );

        $response = $filter->before($request, ['auth.session.list']);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertFalse($payload['success'] ?? true);
        $this->assertSame('Kimlik dogrulama gerekli', $payload['message'] ?? null);
        $this->assertNull($payload['data'] ?? null);
        $this->assertSame('UNAUTHORIZED', $payload['errors']['error_code'] ?? null);
        $this->assertArrayHasKey('request_id', $payload['meta'] ?? []);
    }

    public function testInvalidPermissionParametresiFailFast(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->willReturn('Bearer test-token');
        RequestRuntime::setAuthContext([
            'user_id' => 10,
            'company_id' => 2,
        ]);

        $matrix = $this->createMock(PermissionMatrixService::class);
        $matrix->expects($this->once())
            ->method('assertPermissionKnown')
            ->with('unknown.permission')
            ->willThrowException(new PermissionNotFoundException('x'));

        $filter = new PermissionFilter($matrix, $this->createMock(AuthorizationService::class));

        $this->expectException(PermissionNotFoundException::class);
        $filter->before($request, ['unknown.permission']);
    }

    public function testAuthorizationAllowedIseGecisYapar(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->willReturn('Bearer test-token');
        RequestRuntime::setAuthContext([
            'user_id' => 10,
            'company_id' => 2,
        ]);

        $matrix = $this->createMock(PermissionMatrixService::class);
        $matrix->expects($this->once())->method('assertPermissionKnown')->with('auth.session.list');

        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects($this->once())
            ->method('authorize')
            ->with(10, 2, 'auth.session.list', null)
            ->willReturn([
                'allowed' => true,
                'reason' => null,
                'permission' => 'auth.session.list',
                'scope' => 'company',
                'is_super_admin' => false,
            ]);

        $filter = new PermissionFilter($matrix, $authorization);
        $this->assertNull($filter->before($request, ['auth.session.list']));
    }

    public function testAuthorizationDeniedIse403Doner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->willReturn('Bearer test-token');
        RequestRuntime::setAuthContext([
            'user_id' => 10,
            'company_id' => 2,
        ]);

        $matrix = $this->createMock(PermissionMatrixService::class);
        $matrix->method('assertPermissionKnown');

        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->method('authorize')->willReturn([
            'allowed' => false,
            'reason' => 'permission_missing',
            'permission' => 'auth.session.list',
            'scope' => 'company',
            'is_super_admin' => false,
        ]);

        $filter = new PermissionFilter($matrix, $authorization);
        $response = $filter->before($request, ['auth.session.list']);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertFalse($payload['success'] ?? true);
        $this->assertSame('Bu islem icin yetkiniz yok', $payload['message'] ?? null);
        $this->assertNull($payload['data'] ?? null);
        $this->assertSame('FORBIDDEN', $payload['errors']['error_code'] ?? null);
        $this->assertSame('permission_missing', $payload['errors']['reason'] ?? null);
        $this->assertSame('auth.session.list', $payload['errors']['permission'] ?? null);
        $this->assertSame('company', $payload['errors']['scope'] ?? null);
        $this->assertArrayHasKey('request_id', $payload['meta'] ?? []);
    }
}

