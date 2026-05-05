<?php

namespace Tests\Unit\Filters;

use App\Filters\RoleFilter;
use App\Support\RequestRuntime;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;

final class RoleFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        RequestRuntime::clearAuthContext();
        parent::tearDown();
    }

    public function testContextYoksa401Doner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $filter = new RoleFilter();

        $response = $filter->before($request, ['company_admin']);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertFalse($payload['success'] ?? true);
        $this->assertSame('Kimlik dogrulama gerekli', $payload['message'] ?? null);
        $this->assertNull($payload['data'] ?? null);
        $this->assertSame('UNAUTHORIZED', $payload['errors']['error_code'] ?? null);
        $this->assertArrayHasKey('request_id', $payload['meta'] ?? []);
    }

    public function testKullaniciRoleSahipseGecisYapar(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->willReturn('Bearer test-token');
        RequestRuntime::setAuthContext([
            'user_id' => 10,
            'company_id' => 2,
            'roles' => ['company_admin', 'editor'],
        ]);

        $filter = new RoleFilter();
        $this->assertNull($filter->before($request, ['company_admin']));
    }

    public function testKullaniciRoleSahipDegilse403Doner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->willReturn('Bearer test-token');
        RequestRuntime::setAuthContext([
            'user_id' => 10,
            'company_id' => 2,
            'roles' => ['editor'],
        ]);

        $filter = new RoleFilter();
        $response = $filter->before($request, ['super_admin,company_admin']);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertFalse($payload['success'] ?? true);
        $this->assertSame('Bu islem icin yetkiniz yok', $payload['message'] ?? null);
        $this->assertNull($payload['data'] ?? null);
        $this->assertSame('FORBIDDEN', $payload['errors']['error_code'] ?? null);
        $this->assertArrayHasKey('request_id', $payload['meta'] ?? []);
    }
}

