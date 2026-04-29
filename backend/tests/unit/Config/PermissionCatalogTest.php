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
            $this->assertSame(1, preg_match('/^[a-z]+\.[a-z]+(\.[a-z]+)*$/', $code), $code);
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

