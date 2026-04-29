<?php

namespace Tests\Unit\Auth;

use App\Controllers\Api\V1\AuthController;
use CodeIgniter\Test\CIUnitTestCase;

final class AuthControllerShapeTest extends CIUnitTestCase
{
    public function testBeklenenMethodlarMevcut(): void
    {
        $methods = get_class_methods(AuthController::class);

        $expected = [
            'login',
            'refresh',
            'logout',
            'me',
            'forgotPassword',
            'resetPassword',
        ];

        foreach ($expected as $method) {
            $this->assertContains($method, $methods);
        }
    }
}
