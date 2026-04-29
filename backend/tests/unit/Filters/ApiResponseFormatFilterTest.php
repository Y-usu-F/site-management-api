<?php

namespace Tests\Unit\Filters;

use App\Filters\ApiResponseFormatFilter;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

final class ApiResponseFormatFilterTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = 'api/v1/auth/me';
        $_SERVER['HTTP_X_REQUEST_ID'] = 'req_test_12345678';
    }

    public function testBosResponseStandartZarfaDoner(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = new Response(new App());
        $response->setStatusCode(200);
        $response->setBody('');

        $filter = new ApiResponseFormatFilter();
        $formatted = $filter->after($request, $response);

        $body = json_decode((string) $formatted->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('meta', $body);
        $this->assertSame('req_test_12345678', $body['meta']['request_id']);
    }

    public function testMevcutZarfCiftWrapYapilmaz(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = new Response(new App());
        $response->setJSON([
            'success' => true,
            'message' => 'ok',
            'data' => ['x' => 1],
            'errors' => null,
            'meta' => [],
        ]);

        $filter = new ApiResponseFormatFilter();
        $formatted = $filter->after($request, $response);

        $body = json_decode((string) $formatted->getBody(), true);
        $this->assertSame(['x' => 1], $body['data']);
        $this->assertSame('req_test_12345678', $body['meta']['request_id']);
    }
}
