<?php

namespace Tests\Unit\Auth;

use App\Models\UserRefreshTokenModel;
use App\Services\Auth\LogoutService;
use App\Services\Auth\RefreshTokenService;
use CodeIgniter\Test\CIUnitTestCase;

final class LogoutServiceTest extends CIUnitTestCase
{
    public function testRefreshTokenVarkenFamilyRevokeCalisir(): void
    {
        $refreshService = $this->createMock(RefreshTokenService::class);
        $refreshService->expects($this->once())
            ->method('revokeFamily')
            ->with('a.b.c');

        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->expects($this->never())->method('revokeSessionWithReason');

        $service = new LogoutService($refreshService, $model);
        $result = $service->logout(10, 12, 'a.b.c');

        $this->assertTrue($result['clear_refresh_cookie']);
        $this->assertTrue($result['revoked']);
    }

    public function testRefreshTokenYoksaCurrentSessionRevokeEdilir(): void
    {
        $refreshService = $this->createMock(RefreshTokenService::class);
        $refreshService->expects($this->never())->method('revokeFamily');

        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->expects($this->once())
            ->method('revokeSessionWithReason')
            ->with(10, 12, 'user_logout', 10)
            ->willReturn(true);

        $service = new LogoutService($refreshService, $model);
        $result = $service->logout(10, 12, null);

        $this->assertTrue($result['clear_refresh_cookie']);
        $this->assertTrue($result['revoked']);
    }
}
