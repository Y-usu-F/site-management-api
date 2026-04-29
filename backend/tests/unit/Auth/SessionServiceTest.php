<?php

namespace Tests\Unit\Auth;

use App\Exceptions\UnauthorizedException;
use App\Models\UserRefreshTokenModel;
use App\Services\Auth\SessionService;
use CodeIgniter\Test\CIUnitTestCase;

final class SessionServiceTest extends CIUnitTestCase
{
    public function testListSessionsReturnsOnlyActiveMappedPayload(): void
    {
        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->expects($this->once())
            ->method('getActiveSessionsByUser')
            ->with(10)
            ->willReturn([
                [
                    'id' => 12,
                    'device_name' => 'Chrome on Windows',
                    'created_ip' => '95.10.10.10',
                    'created_user_agent' => 'UA',
                    'created_at' => '2026-04-26 09:10:00',
                    'last_used_at' => '2026-04-26 10:15:00',
                    'expires_at' => '2026-05-26 09:10:00',
                ],
            ]);

        $service = new SessionService($model);
        $sessions = $service->listSessions(10, 12);

        $this->assertCount(1, $sessions);
        $this->assertTrue($sessions[0]['current_session']);
        $this->assertSame('Chrome on Windows', $sessions[0]['device_name']);
    }

    public function testRevokeSessionRejectsAnotherUsersSession(): void
    {
        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->expects($this->once())
            ->method('findActiveTokenById')
            ->with(18)
            ->willReturn([
                'id' => 18,
                'user_id' => 99,
            ]);
        $model->expects($this->never())->method('revokeSession');

        $service = new SessionService($model);

        $this->expectException(UnauthorizedException::class);
        $service->revokeSession(10, 18);
    }

    public function testRevokeSessionRevokesUsersOwnSession(): void
    {
        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->method('findActiveTokenById')->with(20)->willReturn([
            'id' => 20,
            'user_id' => 10,
        ]);
        $model->expects($this->once())
            ->method('revokeSession')
            ->with(10, 20, 10)
            ->willReturn(true);

        $service = new SessionService($model);
        $result = $service->revokeSession(10, 20);

        $this->assertTrue($result['revoked']);
        $this->assertSame(20, $result['session_id']);
    }

    public function testRevokeAllExcludesCurrentSessionWhenProvided(): void
    {
        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->expects($this->once())
            ->method('revokeAllSessions')
            ->with(10, 12)
            ->willReturn(3);

        $service = new SessionService($model);
        $result = $service->revokeAllSessions(10, 12);

        $this->assertSame(3, $result['affected']);
        $this->assertSame(12, $result['excluded_session_id']);
    }

    public function testRevokeAllCanIncludeCurrentSession(): void
    {
        $model = $this->createMock(UserRefreshTokenModel::class);
        $model->expects($this->once())
            ->method('revokeAllSessions')
            ->with(10, null)
            ->willReturn(4);

        $service = new SessionService($model);
        $result = $service->revokeAllSessions(10, null);

        $this->assertSame(4, $result['affected']);
        $this->assertNull($result['excluded_session_id']);
    }
}
