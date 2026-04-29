<?php

namespace Tests\Unit\Routes;

use CodeIgniter\Test\CIUnitTestCase;
use Config\PermissionCatalog;

final class PermissionRouteBindingTest extends CIUnitTestCase
{
    private string $routesPath = ROOTPATH . 'app/Config/Routes.php';
    private string $sessionControllerPath = ROOTPATH . 'app/Controllers/Api/Auth/AuthSessionController.php';

    public function testRoutesIcindekiPermissionKodlariKatalogdaVardir(): void
    {
        $routesContent = (string) file_get_contents($this->routesPath);
        preg_match_all('/permission:([a-z]+\.[a-z]+(?:\.[a-z]+)*)/', $routesContent, $matches);
        $routePermissions = array_values(array_unique($matches[1] ?? []));

        $catalog = new PermissionCatalog();
        foreach ($routePermissions as $permissionCode) {
            $this->assertTrue(
                $catalog->exists($permissionCode),
                'Routes permission katalogda yok: ' . $permissionCode
            );
        }
    }

    public function testAuthSessionRouteBindingleriDogrudur(): void
    {
        $routesContent = (string) file_get_contents($this->routesPath);

        $this->assertStringContainsString("permission:auth.me.view", $routesContent);
        $this->assertStringContainsString("permission:auth.logout", $routesContent);
        $this->assertStringContainsString("permission:auth.session.list", $routesContent);
        $this->assertStringContainsString("permission:auth.session.revoke", $routesContent);
        $this->assertStringContainsString("permission:auth.session.revoke.all", $routesContent);
    }

    public function testPermissionFilterSirasiDogrudur(): void
    {
        $routesContent = (string) file_get_contents($this->routesPath);

        $this->assertMatchesRegularExpression(
            "/'auth-token',\\s*'active-user',\\s*'permission:auth\\.me\\.view'/",
            $routesContent
        );
        $this->assertMatchesRegularExpression(
            "/'auth-token',\\s*'active-user',\\s*'permission:auth\\.logout'/",
            $routesContent
        );
        $this->assertMatchesRegularExpression(
            "/'auth-token',\\s*'active-user',\\s*'permission:auth\\.session\\.list'/",
            $routesContent
        );
        $this->assertMatchesRegularExpression(
            "/'auth-token',\\s*'active-user',\\s*'permission:auth\\.session\\.revoke'/",
            $routesContent
        );
        $this->assertMatchesRegularExpression(
            "/'auth-token',\\s*'active-user',\\s*'permission:auth\\.session\\.revoke\\.all'/",
            $routesContent
        );
    }

    public function testTumProtectedRoutelardaPermissionFiltresiVardir(): void
    {
        $routesContent = (string) file_get_contents($this->routesPath);
        preg_match_all(
            '/\$routes->(?:get|post|put|patch|delete)\([^\n]+\[(?:.|\n)*?\'filter\'\s*=>\s*\[(.*?)\](?:.|\n)*?\]\);/m',
            $routesContent,
            $matches
        );

        $filterBlocks = $matches[1] ?? [];
        foreach ($filterBlocks as $filterBlock) {
            $hasAuthToken = str_contains($filterBlock, "'auth-token'");
            $hasActiveUser = str_contains($filterBlock, "'active-user'");
            $hasPermission = str_contains($filterBlock, "'permission:");

            if ($hasAuthToken && $hasActiveUser) {
                $this->assertTrue($hasPermission, 'Protected route permission filtresi eksik: ' . trim($filterBlock));
            }
        }
    }

    public function testControllerIcindePermissionCheckYoktur(): void
    {
        $controllerContent = (string) file_get_contents($this->sessionControllerPath);

        $this->assertStringNotContainsString('userHasPermission(', $controllerContent);
        $this->assertStringNotContainsString('authorize(', $controllerContent);
        $this->assertStringNotContainsString('ensureAuthorized(', $controllerContent);
    }
}

