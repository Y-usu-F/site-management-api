<?php

namespace Tests\Unit\Filters;

use App\Filters\ActiveUserFilter;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;

final class ActiveUserFilterTest extends CIUnitTestCase
{
    public function testContextteUserYoksaReddedilir(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $filter = new ActiveUserFilter($this->createMock(UserModel::class));
        $response = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }
}
