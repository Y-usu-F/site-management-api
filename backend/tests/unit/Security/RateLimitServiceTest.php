<?php

namespace Tests\Unit\Security;

use App\Services\Security\RateLimitService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

final class RateLimitServiceTest extends CIUnitTestCase
{
    public function testEmailNormalizeEdilir(): void
    {
        $service = new RateLimitService();
        $this->assertSame('abc@example.com', $service->normalizeEmail('  Abc@Example.com  '));
    }

    public function testKeyUretimiEmailIpVeEndpointIcerir(): void
    {
        $service = new RateLimitService();

        $userAgent = $this->createMock(UserAgent::class);
        $userAgent->method('getAgentString')->willReturn('UnitTest UA');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getIPAddress')->willReturn('10.0.0.1');
        $request->method('getMethod')->willReturn('post');
        $request->method('getPath')->willReturn('api/v1/auth/login');
        $request->method('getUserAgent')->willReturn($userAgent);
        $request->method('getJSON')->with(true)->willReturn(['email' => 'USER@Example.com']);

        $keyA = $service->buildKey('login', $request);
        $keyB = $service->buildKey('login', $request);

        $this->assertSame($keyA, $keyB);
        $this->assertStringStartsWith('rl_', $keyA);
        $this->assertNotSame('rl_', $keyA);
    }
}
