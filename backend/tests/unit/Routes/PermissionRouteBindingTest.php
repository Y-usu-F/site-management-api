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
        preg_match_all('/permission:([a-z][a-z_]*\.[a-z][a-z_]*(?:\.[a-z][a-z_]*)*)/', $routesContent, $matches);
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
        $this->assertStringContainsString("permission:site.list", $routesContent);
        $this->assertStringContainsString("permission:block.list", $routesContent);
        $this->assertStringContainsString("permission:floor.list", $routesContent);
        $this->assertStringContainsString("permission:unit.list", $routesContent);
        $this->assertStringContainsString("permission:resident.list", $routesContent);
        $this->assertStringContainsString("permission:unit_occupancy.list", $routesContent);
        $this->assertStringContainsString("permission:resident_contact.list", $routesContent);
        $this->assertStringContainsString("permission:resident_vehicle.list", $routesContent);
        $this->assertStringContainsString("permission:due_definition.list", $routesContent);
        $this->assertStringContainsString("permission:due_period.list", $routesContent);
        $this->assertStringContainsString("permission:due_batch.list", $routesContent);
        $this->assertStringContainsString("permission:due_item.list", $routesContent);
        $this->assertStringContainsString("permission:due_period.close", $routesContent);
        $this->assertStringContainsString("permission:due_period.lock", $routesContent);
        $this->assertStringContainsString("permission:due_item.cancel", $routesContent);
        $this->assertStringContainsString("permission:payment.list", $routesContent);
        $this->assertStringContainsString("permission:payment.create_manual", $routesContent);
        $this->assertStringContainsString("permission:payment.view", $routesContent);
        $this->assertStringContainsString("permission:payment.cancel", $routesContent);
        $this->assertStringContainsString("permission:payment_event.list", $routesContent);
        $this->assertStringContainsString("permission:payment_event.view", $routesContent);
        $this->assertStringContainsString("permission:request_category.list", $routesContent);
        $this->assertStringContainsString("permission:request_category.create", $routesContent);
        $this->assertStringContainsString("permission:request_category.view", $routesContent);
        $this->assertStringContainsString("permission:request_category.update", $routesContent);
        $this->assertStringContainsString("permission:request_category.delete", $routesContent);
        $this->assertStringContainsString("permission:service_request.list", $routesContent);
        $this->assertStringContainsString("permission:service_request.create", $routesContent);
        $this->assertStringContainsString("permission:service_request.view", $routesContent);
        $this->assertStringContainsString("permission:service_request.update", $routesContent);
        $this->assertStringContainsString("permission:service_request.assign", $routesContent);
        $this->assertStringContainsString("permission:service_request.resolve", $routesContent);
        $this->assertStringContainsString("permission:service_request.close", $routesContent);
        $this->assertStringContainsString("permission:service_request.cancel", $routesContent);
        $this->assertStringContainsString("permission:service_request_comment.list", $routesContent);
        $this->assertStringContainsString("permission:service_request_comment.create", $routesContent);
        $this->assertStringContainsString("permission:service_request_file.list", $routesContent);
        $this->assertStringContainsString("permission:service_request_file.create", $routesContent);
        $this->assertStringContainsString("permission:service_request_file.delete", $routesContent);
        $this->assertStringContainsString("permission:work_order.list", $routesContent);
        $this->assertStringContainsString("permission:work_order.create", $routesContent);
        $this->assertStringContainsString("permission:work_order.view", $routesContent);
        $this->assertStringContainsString("permission:work_order.update", $routesContent);
        $this->assertStringContainsString("permission:work_order.start", $routesContent);
        $this->assertStringContainsString("permission:work_order.complete", $routesContent);
        $this->assertStringContainsString("permission:work_order.cancel", $routesContent);
        $this->assertStringContainsString("permission:notification_template.list", $routesContent);
        $this->assertStringContainsString("permission:notification_template.create", $routesContent);
        $this->assertStringContainsString("permission:notification_template.view", $routesContent);
        $this->assertStringContainsString("permission:notification_template.update", $routesContent);
        $this->assertStringContainsString("permission:notification_template.delete", $routesContent);
        $this->assertStringContainsString("permission:notification_message.list", $routesContent);
        $this->assertStringContainsString("permission:notification_message.create", $routesContent);
        $this->assertStringContainsString("permission:notification_message.view", $routesContent);
        $this->assertStringContainsString("permission:notification_message.queue", $routesContent);
        $this->assertStringContainsString("permission:notification_message.cancel", $routesContent);
        $this->assertStringContainsString("permission:notification_recipient.list", $routesContent);
        $this->assertStringContainsString("permission:notification_recipient.view", $routesContent);
        $this->assertStringContainsString("permission:notification_recipient.mark_read", $routesContent);
        $this->assertStringContainsString("permission:notification_delivery_log.list", $routesContent);
        $this->assertStringContainsString("permission:notification_delivery_log.view", $routesContent);
        $this->assertStringContainsString("permission:communication_provider.list", $routesContent);
        $this->assertStringContainsString("permission:communication_provider.create", $routesContent);
        $this->assertStringContainsString("permission:communication_provider.view", $routesContent);
        $this->assertStringContainsString("permission:communication_provider.update", $routesContent);
        $this->assertStringContainsString("permission:communication_provider.delete", $routesContent);
        $this->assertStringContainsString("permission:communication_provider.set_default", $routesContent);
        $this->assertStringContainsString("permission:announcement.list", $routesContent);
        $this->assertStringContainsString("permission:announcement.create", $routesContent);
        $this->assertStringContainsString("permission:announcement.view", $routesContent);
        $this->assertStringContainsString("permission:announcement.update", $routesContent);
        $this->assertStringContainsString("permission:announcement.delete", $routesContent);
        $this->assertStringContainsString("permission:announcement.publish", $routesContent);
        $this->assertStringContainsString("permission:announcement.archive", $routesContent);
        $this->assertStringContainsString("permission:announcement.cancel", $routesContent);
        $this->assertStringContainsString("permission:announcement.mark_read", $routesContent);
        $this->assertStringContainsString("permission:announcement.reads.list", $routesContent);
        $this->assertStringContainsString("permission:announcement.targets.list", $routesContent);
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
        preg_match_all('/\$routes->(?:get|post|put|patch|delete)\((?:[^;]|(?:\n))+?\);/m', $routesContent, $matches);
        $routeCalls = $matches[0] ?? [];
        foreach ($routeCalls as $routeCall) {
            $hasAuthToken = str_contains($routeCall, "'auth-token'");
            $hasActiveUser = str_contains($routeCall, "'active-user'");
            if ($hasAuthToken && $hasActiveUser) {
                $this->assertStringContainsString("'permission:", $routeCall, 'Protected route permission filtresi eksik: ' . trim($routeCall));
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

