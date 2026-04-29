<?php

namespace Tests\Unit\Profile;

use App\Models\UserModel;
use App\Services\Profile\ProfileService;
use CodeIgniter\Test\CIUnitTestCase;

final class ProfileServiceTest extends CIUnitTestCase
{
    public function testGetProfileBasariliDoner(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->once())
            ->method('getSafeProfile')
            ->with(10)
            ->willReturn([
                'id' => 10,
                'company_id' => 1,
                'email' => 'user@example.com',
            ]);

        $service = new ProfileService($userModel);
        $result = $service->show(10);

        $this->assertSame(10, $result['id']);
    }

    public function testUpdateProfileWhitelistDisiAlanlariYokSayar(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->once())
            ->method('updateProfileByWhitelist')
            ->with(10, [
                'first_name' => 'Ali',
                'last_name' => 'Veli',
            ])
            ->willReturn(true);

        $userModel->method('getSafeProfile')->willReturn([
            'id' => 10,
            'first_name' => 'Ali',
            'last_name' => 'Veli',
        ]);

        $service = new ProfileService($userModel);
        $service->update(10, [
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'company_id' => 999,
            'status' => 'inactive',
            'password' => 'secret',
        ]);

        $this->assertTrue(true);
    }
}
