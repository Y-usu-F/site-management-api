<?php

namespace Tests\Unit\Policies;

use App\Exceptions\PermissionNotFoundException;
use App\Policies\AuthorizationPolicy;
use App\Services\Auth\AuthorizationService;
use CodeIgniter\Test\CIUnitTestCase;

final class AuthorizationPolicyTest extends CIUnitTestCase
{
    public function testSuperAdminHerDurumdaAllowOlur(): void
    {
        $service = $this->createMock(AuthorizationService::class);
        $service->expects($this->once())
            ->method('authorize')
            ->with(10, 2, 'permission.manage', 2)
            ->willReturn([
                'allowed' => true,
                'reason' => 'super_admin_override',
                'permission' => 'permission.manage',
                'scope' => 'system',
                'is_super_admin' => true,
            ]);

        $policy = new AuthorizationPolicy($service);
        $this->assertTrue($policy->can(10, 2, 'permission.manage', 2));
    }

    public function testCompanyTenantMismatchDenyReasonKorunur(): void
    {
        $service = $this->createMock(AuthorizationService::class);
        $service->expects($this->exactly(2))
            ->method('authorize')
            ->willReturn([
                'allowed' => false,
                'reason' => 'tenant_mismatch',
                'permission' => 'profile.view',
                'scope' => 'company',
                'is_super_admin' => false,
            ]);

        $policy = new AuthorizationPolicy($service);

        $this->assertFalse($policy->can(10, 2, 'profile.view', 99));
        $this->assertSame('tenant_mismatch', $policy->denyReason(10, 2, 'profile.view', 99));
    }

    public function testUnknownPermissionFailFastKorunur(): void
    {
        $service = $this->createMock(AuthorizationService::class);
        $service->expects($this->once())
            ->method('authorize')
            ->with(10, 2, 'unknown.permission', null)
            ->willThrowException(new PermissionNotFoundException('Permission bulunamadi'));

        $policy = new AuthorizationPolicy($service);

        $this->expectException(PermissionNotFoundException::class);
        $policy->authorize(10, 2, 'unknown.permission');
    }
}

