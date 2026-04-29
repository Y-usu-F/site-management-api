<?php

namespace Tests\Unit\Services\Auth;

use App\Models\UserRoleModel;
use App\Services\Auth\RoleService;
use CodeIgniter\Test\CIUnitTestCase;

final class RoleServiceTest extends CIUnitTestCase
{
    public function testGetActiveRolesForUserAktifVeCompanyFiltreliVeriyiDondurur(): void
    {
        $model = $this->createMock(UserRoleModel::class);
        $model->expects($this->once())
            ->method('getActiveRolesForUser')
            ->with(10, 2)
            ->willReturn([
                ['id' => 1, 'role_id' => 3, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 2],
            ]);

        $service = new RoleService($model);
        $roles = $service->getActiveRolesForUser(10, 2);

        $this->assertCount(1, $roles);
        $this->assertSame('editor', $roles[0]['code']);
    }

    public function testGetRoleCodesForUserSonucuTekillestirirVeSiralar(): void
    {
        $model = $this->createMock(UserRoleModel::class);
        $model->method('getActiveRolesForUser')->willReturn([
            ['id' => 1, 'role_id' => 3, 'code' => 'viewer', 'name' => 'Viewer', 'role_version' => 1],
            ['id' => 2, 'role_id' => 4, 'code' => 'editor', 'name' => 'Editor', 'role_version' => 1],
            ['id' => 3, 'role_id' => 5, 'code' => 'viewer', 'name' => 'Viewer2', 'role_version' => 1],
        ]);

        $service = new RoleService($model);
        $codes = $service->getRoleCodesForUser(10, 2);

        $this->assertSame(['editor', 'viewer'], $codes);
    }

    public function testGetRoleVersionForUserModeldekiMaksimumDegeriDondurur(): void
    {
        $model = $this->createMock(UserRoleModel::class);
        $model->expects($this->once())
            ->method('getRoleVersionForUser')
            ->with(10, 2)
            ->willReturn(8);

        $service = new RoleService($model);
        $version = $service->getRoleVersionForUser(10, 2);

        $this->assertSame(8, $version);
    }
}

