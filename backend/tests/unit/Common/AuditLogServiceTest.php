<?php

namespace Tests\Unit\Common;

use App\Models\AuditLogModel;
use App\Services\Common\AuditEventTaxonomy;
use App\Services\Common\AuditLogService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class AuditLogServiceTest extends CIUnitTestCase
{
    public function testSensitiveDataMaskedInMeta(): void
    {
        $model = $this->createMock(AuditLogModel::class);
        $model->expects($this->once())
            ->method('insert')
            ->with($this->callback(static function (array $row): bool {
                $meta = json_decode((string) ($row['meta'] ?? '{}'), true);
                $metaData = $meta['meta'] ?? [];

                return ($metaData['password'] ?? null) === '***'
                    && ($metaData['refresh_token'] ?? null) === '***'
                    && ($metaData['authorization'] ?? null) === '***';
            }))
            ->willReturn(true);

        $service = new AuditLogService($model);
        $ok = $service->recordEvent('auth.login.success', [
            'status' => 'success',
            'meta' => [
                'password' => 'secret',
                'refresh_token' => 'jwt',
                'authorization' => 'Bearer test',
            ],
        ]);

        $this->assertTrue($ok);
    }

    public function testMandatoryStandardFieldsArePersisted(): void
    {
        $model = $this->createMock(AuditLogModel::class);
        $model->expects($this->once())
            ->method('insert')
            ->with($this->callback(static function (array $row): bool {
                $requiredKeys = [
                    'company_id',
                    'actor_user_id',
                    'action',
                    'entity_type',
                    'entity_id',
                    'old_values',
                    'new_values',
                    'ip_address',
                    'user_agent',
                    'request_id',
                    'occurred_at',
                ];

                foreach ($requiredKeys as $requiredKey) {
                    if (! array_key_exists($requiredKey, $row)) {
                        return false;
                    }
                }

                return $row['action'] === 'profile.update.success'
                    && $row['entity_type'] === 'user_profile'
                    && is_string($row['occurred_at'])
                    && trim($row['occurred_at']) !== '';
            }))
            ->willReturn(true);

        $service = new AuditLogService($model);
        $ok = $service->recordEvent('profile.update.success', [
            'company_id' => 42,
            'actor_user_id' => 7,
            'entity_type' => 'user_profile',
            'entity_id' => 99,
        ]);

        $this->assertTrue($ok);
    }

    public function testEventNamingStandardValidation(): void
    {
        $model = $this->createMock(AuditLogModel::class);
        $model->expects($this->never())->method('insert');

        $service = new AuditLogService($model);

        $this->assertFalse($service->recordEvent('Bad Event', []));
        $this->assertTrue(AuditEventTaxonomy::isValidEventName('profile.update.success'));
    }

    public function testOldAndNewValuesWrittenWithSensitiveMasking(): void
    {
        $model = $this->createMock(AuditLogModel::class);
        $model->expects($this->once())
            ->method('insert')
            ->with($this->callback(static function (array $row): bool {
                $oldValues = json_decode((string) ($row['old_values'] ?? '{}'), true);
                $newValues = json_decode((string) ($row['new_values'] ?? '{}'), true);

                return ($oldValues['password'] ?? null) === '***'
                    && ($newValues['token'] ?? null) === '***'
                    && ($oldValues['name'] ?? null) === 'old-name'
                    && ($newValues['name'] ?? null) === 'new-name';
            }))
            ->willReturn(true);

        $service = new AuditLogService($model);
        $ok = $service->recordEvent('profile.update.success', [
            'entity_type' => 'user_profile',
            'entity_id' => 12,
            'old_values' => ['name' => 'old-name', 'password' => 'secret'],
            'new_values' => ['name' => 'new-name', 'token' => 'jwt-value'],
        ]);

        $this->assertTrue($ok);
    }

    public function testAuditFailureDoesNotThrowAndReturnsFalse(): void
    {
        $model = $this->createMock(AuditLogModel::class);
        $model->method('insert')->willThrowException(new RuntimeException('db down'));

        $service = new AuditLogService($model);
        $ok = $service->recordEvent('auth.login.failed', [
            'status' => 'failed',
            'meta' => ['reason' => 'db-failure-test'],
        ]);

        $this->assertFalse($ok);
    }
}
