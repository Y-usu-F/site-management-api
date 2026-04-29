<?php

namespace Tests\Unit\Filters;

use App\Filters\RateLimitFilter;
use App\Services\Common\AuditLogService;
use App\Services\Security\RateLimitService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\SecurityConfig;

final class RateLimitFilterTest extends CIUnitTestCase
{
    public function testEsikAltindaIstekGecer(): void
    {
        $service = $this->createMock(RateLimitService::class);
        $service->method('consume')->willReturn([
            'allowed' => true,
            'retry_after' => 0,
            'remaining' => 3,
            'key' => 'k',
            'backend' => 'fallback',
        ]);

        $filter = new RateLimitFilter(
            $service,
            new SecurityConfig(),
            $this->createMock(AuditLogService::class)
        );

        $request = $this->createMock(RequestInterface::class);
        $this->assertNull($filter->before($request, ['login']));
    }

    public function testEsikUstunde429DonerVeLoginAuditYazar(): void
    {
        $service = $this->createMock(RateLimitService::class);
        $service->method('consume')->willReturn([
            'allowed' => false,
            'retry_after' => 55,
            'remaining' => 0,
            'key' => 'k',
            'backend' => 'fallback',
        ]);
        $service->method('normalizeEmail')->willReturn('test@example.com');

        $audit = $this->createMock(AuditLogService::class);
        $audit->expects($this->once())
            ->method('recordEvent')
            ->with(
                'auth.login.blocked_rate_limit',
                $this->arrayHasKey('meta')
            )
            ->willReturn(true);

        $userAgent = $this->createMock(UserAgent::class);
        $userAgent->method('getAgentString')->willReturn('phpunit');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getJSON')->willReturn(['email' => 'Test@Example.com']);
        $request->method('getIPAddress')->willReturn('127.0.0.1');
        $request->method('getUserAgent')->willReturn($userAgent);

        $filter = new RateLimitFilter($service, new SecurityConfig(), $audit);
        $response = $filter->before($request, ['login']);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('RATE_LIMIT_EXCEEDED', $response->getBody());
    }
}
