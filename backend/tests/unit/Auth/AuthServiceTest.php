<?php

namespace Tests\Unit\Auth;

use App\Exceptions\UnauthorizedException;
use App\Models\UserModel;
use App\Services\Auth\AuthService;
use App\Services\Auth\LogoutService;
use App\Services\Auth\PasswordPolicyService;
use App\Services\Auth\RefreshTokenService;
use App\Services\Auth\TokenService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthConfig;

final class AuthServiceTest extends CIUnitTestCase
{
    public function testLoginBasariliOldugundaTokenDonderirVeSayaciSifirlar(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('findForLogin')->willReturn([
            'id' => 10,
            'company_id' => 2,
            'email' => 'user@example.com',
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'status' => 'active',
            'is_active' => 1,
            'failed_login_count' => 2,
            'locked_until' => null,
        ]);
        $userModel->method('getRoleCodes')->with(10, 2)->willReturn(['employee']);
        $userModel->method('findLatestRefreshTokenId')->with(10, 2)->willReturn(99);
        $userModel->expects($this->once())->method('markLoginSuccess')->with(10)->willReturn(true);

        $tokenService = $this->createMock(TokenService::class);
        $tokenService->method('issueAccessToken')->with(10, 2, ['employee'], 99)->willReturn([
            'token_type' => 'Bearer',
            'access_token' => 'access',
            'expires_in' => 900,
        ]);

        $refreshService = $this->createMock(RefreshTokenService::class);
        $refreshService->method('issue')->with(10, 2, ['employee'])->willReturn('refresh.jwt.token');

        $service = new AuthService(
            userModel: $userModel,
            tokenService: $tokenService,
            refreshTokenService: $refreshService,
            logoutService: $this->createMock(LogoutService::class),
            passwordPolicyService: new PasswordPolicyService(),
            authConfig: new AuthConfig()
        );

        $result = $service->login('user@example.com', 'Password123!');
        $this->assertSame('access', $result['access_token']);
        $this->assertSame('refresh.jwt.token', $result['refresh_token']);
    }

    public function testDeaktifKullaniciLoginOlamaz(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('findForLogin')->willReturn([
            'id' => 10,
            'status' => 'inactive',
            'is_active' => 0,
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);

        $service = new AuthService(
            userModel: $userModel,
            tokenService: $this->createMock(TokenService::class),
            refreshTokenService: $this->createMock(RefreshTokenService::class),
            logoutService: $this->createMock(LogoutService::class),
            passwordPolicyService: new PasswordPolicyService(),
            authConfig: new AuthConfig()
        );

        $this->expectException(UnauthorizedException::class);
        $service->login('user@example.com', 'Password123!');
    }

    public function testHataliSifredeFailedLoginCountArtar(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('findForLogin')->willReturn([
            'id' => 10,
            'status' => 'active',
            'is_active' => 1,
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'failed_login_count' => 1,
            'locked_until' => null,
        ]);
        $userModel->expects($this->once())
            ->method('markLoginFailed')
            ->with(10, 2, $this->anything())
            ->willReturn(true);

        $policy = $this->createMock(PasswordPolicyService::class);
        $policy->method('isLocked')->willReturn(false);
        $policy->method('maxFailedLoginAttempts')->willReturn(5);
        $policy->method('lockDurationSeconds')->willReturn(60);

        $service = new AuthService(
            userModel: $userModel,
            tokenService: $this->createMock(TokenService::class),
            refreshTokenService: $this->createMock(RefreshTokenService::class),
            logoutService: $this->createMock(LogoutService::class),
            passwordPolicyService: $policy,
            authConfig: new AuthConfig()
        );

        $this->expectException(UnauthorizedException::class);
        $service->login('user@example.com', 'WrongPass123!');
    }

    public function testLockedUntilGelecekteyseLoginReddedilir(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('findForLogin')->willReturn([
            'id' => 10,
            'status' => 'active',
            'is_active' => 1,
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'failed_login_count' => 5,
            'locked_until' => date('Y-m-d H:i:s', time() + 600),
        ]);

        $policy = $this->createMock(PasswordPolicyService::class);
        $policy->method('isLocked')->willReturn(true);

        $service = new AuthService(
            userModel: $userModel,
            tokenService: $this->createMock(TokenService::class),
            refreshTokenService: $this->createMock(RefreshTokenService::class),
            logoutService: $this->createMock(LogoutService::class),
            passwordPolicyService: $policy,
            authConfig: new AuthConfig()
        );

        $this->expectException(UnauthorizedException::class);
        $service->login('user@example.com', 'Password123!');
    }
}
