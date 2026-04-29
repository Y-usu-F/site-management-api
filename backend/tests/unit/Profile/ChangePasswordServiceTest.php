<?php

namespace Tests\Unit\Profile;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationApiException;
use App\Models\UserModel;
use App\Models\UserRefreshTokenModel;
use App\Services\Auth\PasswordPolicyService;
use App\Services\Profile\ChangePasswordService;
use CodeIgniter\Test\CIUnitTestCase;

final class ChangePasswordServiceTest extends CIUnitTestCase
{
    public function testEskiSifreYanlissaHataFirlatir(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('find')->willReturn([
            'id' => 10,
            'password_hash' => password_hash('OldPass123!', PASSWORD_DEFAULT),
        ]);

        $service = new ChangePasswordService(
            $userModel,
            $this->createMock(UserRefreshTokenModel::class),
            new PasswordPolicyService()
        );

        $this->expectException(UnauthorizedException::class);
        $service->changePassword(10, 'WrongPass123!', 'NewPass123!', 5);
    }

    public function testYeniSifreEskisiyleAyniysaHataFirlatir(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $hash = password_hash('SamePass123!', PASSWORD_DEFAULT);
        $userModel->method('find')->willReturn([
            'id' => 10,
            'password_hash' => $hash,
        ]);

        $service = new ChangePasswordService(
            $userModel,
            $this->createMock(UserRefreshTokenModel::class),
            new PasswordPolicyService()
        );

        $this->expectException(ValidationApiException::class);
        $service->changePassword(10, 'SamePass123!', 'SamePass123!', 5);
    }

    public function testBasariliPasswordChangeSonrasiDigerSessionlarRevokeEdilir(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('find')->willReturn([
            'id' => 10,
            'password_hash' => password_hash('OldPass123!', PASSWORD_DEFAULT),
        ]);
        $userModel->expects($this->once())->method('updatePassword')->with(10, $this->isType('string'))->willReturn(true);

        $refreshModel = $this->createMock(UserRefreshTokenModel::class);
        $refreshModel->expects($this->once())
            ->method('revokeAllSessionsWithReason')
            ->with(10, 'password_changed', 5, 10)
            ->willReturn(3);

        $service = new ChangePasswordService($userModel, $refreshModel, new PasswordPolicyService());
        $result = $service->changePassword(10, 'OldPass123!', 'NewPass123!', 5);

        $this->assertTrue($result['password_changed']);
        $this->assertSame(3, $result['revoked_sessions']);
    }
}
