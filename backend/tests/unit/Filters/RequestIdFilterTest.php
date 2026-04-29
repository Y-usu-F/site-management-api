<?php

namespace Tests\Unit\Filters;

use App\Filters\RequestIdFilter;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

final class RequestIdFilterTest extends CIUnitTestCase
{
    public function testHeaderVarkenKorunur(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->with('X-Request-Id')->willReturn('req_custom_12345');

        $filter = new RequestIdFilter();
        $filter->before($request);

        $this->assertSame('req_custom_12345', $_SERVER['HTTP_X_REQUEST_ID'] ?? null);
    }

    public function testHeaderYokkenUretilirVeResponseaYazilir(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getHeaderLine')->with('X-Request-Id')->willReturn('');

        $filter = new RequestIdFilter();
        $filter->before($request);

        $response = new Response(new App());
        $filter->after($request, $response);

        $this->assertTrue($response->hasHeader('X-Request-Id'));
    }
}
