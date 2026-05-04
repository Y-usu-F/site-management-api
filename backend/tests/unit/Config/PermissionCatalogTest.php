<?php

namespace Tests\Unit\Config;

use App\Exceptions\InvalidPermissionCodeException;
use App\Exceptions\PermissionNotFoundException;
use Config\PermissionCatalog;
use CodeIgniter\Test\CIUnitTestCase;

final class PermissionCatalogTest extends CIUnitTestCase
{
    public function testKatalogValidateEdilir(): void
    {
        $catalog = new PermissionCatalog();
        $catalog->validateCatalog();
        $this->assertNotEmpty($catalog->all());
    }

    public function testKodlarBenzersizdir(): void
    {
        $catalog = new PermissionCatalog();
        $codes = $catalog->codes();
        $this->assertSame(count($codes), count(array_unique($codes)));
    }

    public function testTumKodlarRegexFormatinaUyar(): void
    {
        $catalog = new PermissionCatalog();
        foreach ($catalog->codes() as $code) {
            $this->assertSame(1, preg_match('/^[a-z][a-z_]*\.[a-z][a-z_]*(\.[a-z][a-z_]*)*$/', $code), $code);
        }
    }

    public function testTumKodlarLowercase(): void
    {
        $catalog = new PermissionCatalog();
        foreach ($catalog->codes() as $code) {
            $this->assertSame(strtolower($code), $code);
        }
    }

    public function testScopeSadeceSystemVeyaCompanyOlur(): void
    {
        $catalog = new PermissionCatalog();
        foreach ($catalog->all() as $permission) {
            $this->assertContains($permission['scope'], ['system', 'company']);
        }
    }

    public function testExistsBilinenPermissionIcinTrueDoner(): void
    {
        $catalog = new PermissionCatalog();
        $this->assertTrue($catalog->exists('auth.session.list'));
        $this->assertTrue($catalog->exists('auth.me.view'));
        $this->assertTrue($catalog->exists('auth.logout'));
        $this->assertTrue($catalog->exists('profile.view'));
        $this->assertTrue($catalog->exists('profile.update'));
        $this->assertTrue($catalog->exists('profile.password.change'));
        $this->assertTrue($catalog->exists('site.list'));
        $this->assertTrue($catalog->exists('block.list'));
        $this->assertTrue($catalog->exists('floor.list'));
        $this->assertTrue($catalog->exists('unit.list'));
        $this->assertTrue($catalog->exists('resident.list'));
        $this->assertTrue($catalog->exists('unit_occupancy.list'));
        $this->assertTrue($catalog->exists('resident_contact.list'));
        $this->assertTrue($catalog->exists('resident_vehicle.list'));
        $this->assertTrue($catalog->exists('due_definition.list'));
        $this->assertTrue($catalog->exists('due_period.list'));
        $this->assertTrue($catalog->exists('due_batch.list'));
        $this->assertTrue($catalog->exists('due_item.list'));
        $this->assertTrue($catalog->exists('due_period.close'));
        $this->assertTrue($catalog->exists('due_period.lock'));
        $this->assertTrue($catalog->exists('due_item.cancel'));
        $this->assertTrue($catalog->exists('payment.list'));
        $this->assertTrue($catalog->exists('payment.create_manual'));
        $this->assertTrue($catalog->exists('payment.view'));
        $this->assertTrue($catalog->exists('payment.cancel'));
        $this->assertTrue($catalog->exists('payment_event.list'));
        $this->assertTrue($catalog->exists('payment_event.view'));
        $this->assertTrue($catalog->exists('request_category.list'));
        $this->assertTrue($catalog->exists('request_category.create'));
        $this->assertTrue($catalog->exists('request_category.view'));
        $this->assertTrue($catalog->exists('request_category.update'));
        $this->assertTrue($catalog->exists('request_category.delete'));
        $this->assertTrue($catalog->exists('service_request.list'));
        $this->assertTrue($catalog->exists('service_request.create'));
        $this->assertTrue($catalog->exists('service_request.view'));
        $this->assertTrue($catalog->exists('service_request.update'));
        $this->assertTrue($catalog->exists('service_request.assign'));
        $this->assertTrue($catalog->exists('service_request.resolve'));
        $this->assertTrue($catalog->exists('service_request.close'));
        $this->assertTrue($catalog->exists('service_request.cancel'));
        $this->assertTrue($catalog->exists('service_request_comment.list'));
        $this->assertTrue($catalog->exists('service_request_comment.create'));
        $this->assertTrue($catalog->exists('service_request_file.list'));
        $this->assertTrue($catalog->exists('service_request_file.create'));
        $this->assertTrue($catalog->exists('service_request_file.delete'));
        $this->assertTrue($catalog->exists('work_order.list'));
        $this->assertTrue($catalog->exists('work_order.create'));
        $this->assertTrue($catalog->exists('work_order.view'));
        $this->assertTrue($catalog->exists('work_order.update'));
        $this->assertTrue($catalog->exists('work_order.start'));
        $this->assertTrue($catalog->exists('work_order.complete'));
        $this->assertTrue($catalog->exists('work_order.cancel'));
        $this->assertTrue($catalog->exists('notification_template.list'));
        $this->assertTrue($catalog->exists('notification_template.create'));
        $this->assertTrue($catalog->exists('notification_template.view'));
        $this->assertTrue($catalog->exists('notification_template.update'));
        $this->assertTrue($catalog->exists('notification_template.delete'));
        $this->assertTrue($catalog->exists('notification_message.list'));
        $this->assertTrue($catalog->exists('notification_message.create'));
        $this->assertTrue($catalog->exists('notification_message.view'));
        $this->assertTrue($catalog->exists('notification_message.queue'));
        $this->assertTrue($catalog->exists('notification_message.cancel'));
        $this->assertTrue($catalog->exists('notification_recipient.list'));
        $this->assertTrue($catalog->exists('notification_recipient.view'));
        $this->assertTrue($catalog->exists('notification_recipient.mark_read'));
        $this->assertTrue($catalog->exists('notification_delivery_log.list'));
        $this->assertTrue($catalog->exists('notification_delivery_log.view'));
        $this->assertTrue($catalog->exists('communication_provider.list'));
        $this->assertTrue($catalog->exists('communication_provider.create'));
        $this->assertTrue($catalog->exists('communication_provider.view'));
        $this->assertTrue($catalog->exists('communication_provider.update'));
        $this->assertTrue($catalog->exists('communication_provider.delete'));
        $this->assertTrue($catalog->exists('communication_provider.set_default'));
        $this->assertTrue($catalog->exists('announcement.list'));
        $this->assertTrue($catalog->exists('announcement.create'));
        $this->assertTrue($catalog->exists('announcement.view'));
        $this->assertTrue($catalog->exists('announcement.update'));
        $this->assertTrue($catalog->exists('announcement.delete'));
        $this->assertTrue($catalog->exists('announcement.publish'));
        $this->assertTrue($catalog->exists('announcement.archive'));
        $this->assertTrue($catalog->exists('announcement.cancel'));
        $this->assertTrue($catalog->exists('announcement.mark_read'));
        $this->assertTrue($catalog->exists('announcement.reads.list'));
        $this->assertTrue($catalog->exists('announcement.targets.list'));
    }

    public function testExistsBilinmeyenPermissionIcinFalseDoner(): void
    {
        $catalog = new PermissionCatalog();
        $this->assertFalse($catalog->exists('unknown.permission.code'));
    }

    public function testGetBilinmeyendeExceptionFirlatir(): void
    {
        $catalog = new PermissionCatalog();
        $this->expectException(PermissionNotFoundException::class);
        $catalog->get('unknown.permission.code');
    }

    public function testScopeOfDogruScopeDoner(): void
    {
        $catalog = new PermissionCatalog();
        $this->assertSame('company', $catalog->scopeOf('auth.session.list'));
        $this->assertSame('system', $catalog->scopeOf('permission.manage'));
    }

    public function testValidateCatalogDuplicateKoddaFailFastYapar(): void
    {
        $catalog = new PermissionCatalog();
        $permissions = $catalog->all();
        $permissions[] = $permissions[0];
        $this->setPrivateProperty($catalog, 'permissions', $permissions);

        $this->expectException(InvalidPermissionCodeException::class);
        $catalog->validateCatalog();
    }

    public function testValidateCatalogGecersizScopeIcinFailFastYapar(): void
    {
        $catalog = new PermissionCatalog();
        $permissions = $catalog->all();
        $permissions[0]['scope'] = 'tenant';
        $this->setPrivateProperty($catalog, 'permissions', $permissions);

        $this->expectException(InvalidPermissionCodeException::class);
        $catalog->validateCatalog();
    }

    public function testAssertExistsInvalidFormattaExceptionFirlatir(): void
    {
        $catalog = new PermissionCatalog();
        $this->expectException(InvalidPermissionCodeException::class);
        $catalog->assertExists('AUTH.SESSION.LIST');
    }
}

