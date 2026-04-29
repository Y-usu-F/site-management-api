<?php

namespace Tests\Unit\Profile;

use App\Controllers\Api\V1\ProfileController;
use CodeIgniter\Test\CIUnitTestCase;

final class ProfileControllerShapeTest extends CIUnitTestCase
{
    public function testBeklenenMethodlarMevcut(): void
    {
        $methods = get_class_methods(ProfileController::class);

        $expected = [
            'show',
            'update',
            'changePassword',
        ];

        foreach ($expected as $method) {
            $this->assertContains($method, $methods);
        }
    }
}
