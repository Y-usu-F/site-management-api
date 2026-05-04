<?php

namespace Tests\Unit\Services\Auth;

use App\Exceptions\PermissionNotFoundException;
use App\Models\PermissionModel;
use App\Services\Auth\PermissionMatrixService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\PermissionCatalog;

final class PermissionMatrixServiceTest extends CIUnitTestCase
{
    public function testMatrisKatalogUyumuPass(): void
    {
        $routesFile = tempnam(sys_get_temp_dir(), 'routes_');
        file_put_contents(
            $routesFile,
            <<<'PHP'
<?php
$routes->get('api/v1/protected', 'Api\V1\Foo::bar', [
    'filter' => ['auth-token', 'active-user', 'permission:auth.session.list'],
]);
PHP
        );

        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('all')->willReturn([
            ['code' => 'auth.session.list', 'is_active' => true],
            ['code' => 'profile.view', 'is_active' => true],
        ]);

        $model = $this->createMock(PermissionModel::class);
        $model->method('getAllPermissionsForMatrix')->willReturn([
            ['code' => 'auth.session.list', 'is_active' => 1, 'deprecated_at' => null],
            ['code' => 'profile.view', 'is_active' => 1, 'deprecated_at' => null],
        ]);
        $model->method('getRolePermissionMatrixRows')->willReturn([
            ['permission_id' => 1, 'role_permission_active' => 1, 'permission_code' => 'auth.session.list', 'permission_active' => 1, 'permission_deprecated_at' => null],
        ]);

        $service = new PermissionMatrixService($catalog, $model, $routesFile);
        $result = $service->validateAll();
        @unlink($routesFile);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    public function testDbdeKatalogdaOlmayanPermissionFail(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('all')->willReturn([
            ['code' => 'auth.session.list', 'is_active' => true],
        ]);

        $model = $this->createMock(PermissionModel::class);
        $model->method('getAllPermissionsForMatrix')->willReturn([
            ['code' => 'unknown.permission', 'is_active' => 1, 'deprecated_at' => null],
        ]);
        $model->method('getRolePermissionMatrixRows')->willReturn([]);

        $service = new PermissionMatrixService($catalog, $model);
        $result = $service->validateCatalogAgainstDatabase();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testKatalogdaOlupDbdeOlmayanPermissionWarningVerir(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('all')->willReturn([
            ['code' => 'auth.session.list', 'is_active' => true],
            ['code' => 'auth.session.revoke', 'is_active' => true],
        ]);

        $model = $this->createMock(PermissionModel::class);
        $model->method('getAllPermissionsForMatrix')->willReturn([
            ['code' => 'auth.session.list', 'is_active' => 1, 'deprecated_at' => null],
        ]);
        $model->method('getRolePermissionMatrixRows')->willReturn([]);

        $service = new PermissionMatrixService($catalog, $model);
        $result = $service->validateCatalogAgainstDatabase();

        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function testDeprecatedPermissionMappingTespitEdilir(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('all')->willReturn([]);

        $model = $this->createMock(PermissionModel::class);
        $model->method('getAllPermissionsForMatrix')->willReturn([]);
        $model->method('getRolePermissionMatrixRows')->willReturn([
            ['permission_id' => 2, 'role_permission_active' => 1, 'permission_code' => 'profile.view', 'permission_active' => 1, 'permission_deprecated_at' => '2026-04-26 00:00:00'],
        ]);

        $service = new PermissionMatrixService($catalog, $model);
        $result = $service->validateRolePermissionMatrix();

        $this->assertNotEmpty($result['warnings']);
    }

    public function testInactivePermissionMappingTespitEdilir(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('all')->willReturn([]);

        $model = $this->createMock(PermissionModel::class);
        $model->method('getAllPermissionsForMatrix')->willReturn([]);
        $model->method('getRolePermissionMatrixRows')->willReturn([
            ['permission_id' => 2, 'role_permission_active' => 1, 'permission_code' => 'profile.view', 'permission_active' => 0, 'permission_deprecated_at' => null],
        ]);

        $service = new PermissionMatrixService($catalog, $model);
        $result = $service->validateRolePermissionMatrix();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testAssertPermissionKnownUnknownCodeIcinExceptionFirlatir(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('assertExists')->willThrowException(new PermissionNotFoundException('x'));

        $service = new PermissionMatrixService($catalog, $this->createMock(PermissionModel::class));

        $this->expectException(PermissionNotFoundException::class);
        $service->assertPermissionKnown('unknown.permission');
    }

    public function testValidateAllValidFalseIkenErrorsDoludur(): void
    {
        $catalog = $this->createMock(PermissionCatalog::class);
        $catalog->method('all')->willReturn([
            ['code' => 'auth.session.list', 'is_active' => true],
        ]);

        $model = $this->createMock(PermissionModel::class);
        $model->method('getAllPermissionsForMatrix')->willReturn([
            ['code' => 'unknown.permission', 'is_active' => 1, 'deprecated_at' => null],
        ]);
        $model->method('getRolePermissionMatrixRows')->willReturn([
            ['permission_id' => 99, 'role_permission_active' => 1, 'permission_code' => '', 'permission_active' => 0, 'permission_deprecated_at' => null],
        ]);

        $service = new PermissionMatrixService($catalog, $model);
        $result = $service->validateAll();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testRoutePermissionCoverageProtectedRouteIcinPermissionBekler(): void
    {
        $routesFile = tempnam(sys_get_temp_dir(), 'routes_');
        file_put_contents(
            $routesFile,
            <<<'PHP'
<?php
$routes->get('api/v1/protected', 'Api\V1\Foo::bar', [
    'filter' => ['auth-token', 'active-user'],
]);
PHP
        );

        $service = new PermissionMatrixService(
            $this->createMock(PermissionCatalog::class),
            $this->createMock(PermissionModel::class),
            $routesFile
        );
        $result = $service->validateRoutePermissionCoverage();
        @unlink($routesFile);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testRoutePermissionCoveragePermissionVarkenPassOlur(): void
    {
        $routesFile = tempnam(sys_get_temp_dir(), 'routes_');
        file_put_contents(
            $routesFile,
            <<<'PHP'
<?php
$routes->get('api/v1/protected', 'Api\V1\Foo::bar', [
    'filter' => ['auth-token', 'active-user', 'permission:auth.session.list'],
]);
PHP
        );

        $service = new PermissionMatrixService(
            $this->createMock(PermissionCatalog::class),
            $this->createMock(PermissionModel::class),
            $routesFile
        );
        $result = $service->validateRoutePermissionCoverage();
        @unlink($routesFile);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }
}

