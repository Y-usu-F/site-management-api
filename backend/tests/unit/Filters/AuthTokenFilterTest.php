<?php

namespace Tests\Unit\Filters;

use App\Filters\AuthTokenFilter;
use App\Services\Auth\TokenService;
use App\Services\RateLimitKeyService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;

final class AuthTokenFilterTest extends CIUnitTestCase
{
    public function testTokenYoksaTokenMissingDoner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');

        $filter = new AuthTokenFilter(
            $this->createMock(RateLimitKeyService::class),
            $this->createMock(TokenService::class)
        );

        $response = $filter->before($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testBearerFormatYanlissaTokenInvalidDoner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Basic abc');

        $filter = new AuthTokenFilter(
            $this->createMock(RateLimitKeyService::class),
            $this->createMock(TokenService::class)
        );

        $response = $filter->before($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }
}
