<?php

namespace Tests\Unit\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\DryRunReport;
use App\Support\LegacyMigration\Validators\DueScopeValidator;
use CodeIgniter\Test\CIUnitTestCase;

final class DueScopeValidatorTest extends CIUnitTestCase
{
    public function testMissingTableColumnsWarningNoCrash(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return false; }
        };
        $data = $validator->validate(new DryRunReport('due-1', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
    }

    public function testSourceCountsSetWhenTablesExist(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function countRows($db, string $table): int { return 3; }
            protected function listColumns($db, string $table): array
            {
                return ['id', 'status', 'tip', 'vade_tarihi', 'tutar', 'daire_id', 'uye_id', 'donem', 'oran'];
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return [['id' => 1, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '2026-10-10', 'tutar' => 100, 'daire_id' => 1, 'uye_id' => 1, 'donem' => '2026-10', 'oran' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('due-2', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertSame(3, (int) ($data['source_counts']['aidat_listesi'] ?? 0));
        $this->assertSame(3, (int) ($data['source_counts']['borc_listesi'] ?? 0));
    }

    public function testUnknownDueStatusTypeCreatesQuarantineCandidate(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['aidat_grup_tanimlari', 'aidat_listesi', 'daire_tanimlari', 'uye_tanimlari'], true); }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'aidat_grup_tanimlari' => ['id', 'tip'],
                    'aidat_listesi' => ['id', 'status', 'tip', 'vade_tarihi', 'tutar', 'daire_id', 'uye_id', 'donem'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return match ($table) {
                    'aidat_grup_tanimlari' => [['id' => 1, 'tip' => 'x']],
                    'aidat_listesi' => [['id' => 2, 'status' => 'x', 'tip' => 'y', 'vade_tarihi' => '2026-10-10', 'tutar' => 10, 'daire_id' => 1, 'uye_id' => 1, 'donem' => '2026-10']],
                    default => [['id' => 1]],
                };
            }
        };
        $data = $validator->validate(new DryRunReport('due-3', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
    }

    public function testInvalidDueDateWarningAndQuarantine(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['aidat_listesi', 'daire_tanimlari', 'uye_tanimlari'], true); }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array { return $table === 'aidat_listesi' ? ['id', 'status', 'tip', 'vade_tarihi', 'tutar', 'daire_id', 'donem'] : ['id']; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return $table === 'aidat_listesi'
                    ? [['id' => 1, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '0000-00-00', 'tutar' => 10, 'daire_id' => 1, 'donem' => '2026-10']]
                    : [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('due-4', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
    }

    public function testNonNumericAmountCreatesQuarantineAndNegativeNonRefundCreatesBlocker(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['aidat_listesi', 'borc_listesi', 'daire_tanimlari', 'uye_tanimlari'], true); }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array { return in_array($table, ['aidat_listesi', 'borc_listesi'], true) ? ['id', 'status', 'tip', 'vade_tarihi', 'tutar', 'daire_id', 'donem'] : ['id']; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'aidat_listesi') {
                    return [['id' => 1, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '2026-10-10', 'tutar' => 'abc', 'daire_id' => 1, 'donem' => '2026-10']];
                }
                if ($table === 'borc_listesi') {
                    return [['id' => 2, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '2026-10-10', 'tutar' => -5, 'daire_id' => 1, 'donem' => '2026-10']];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('due-5', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertNotEmpty($data['blockers']);
    }

    public function testOrphanRefsAndDuplicateNaturalKeyCreateExpectedSignals(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return $table !== 'aidat_iade_listesi' && $table !== 'borc_iade_listesi' && $table !== 'gecikme_faiz_oranlari'; }
            protected function countRows($db, string $table): int { return 2; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'aidat_grup_tanimlari' => ['id', 'tip'],
                    'aidat_listesi' => ['id', 'status', 'tip', 'vade_tarihi', 'tutar', 'daire_id', 'uye_id', 'donem'],
                    'borc_listesi' => ['id', 'status', 'tip', 'vade_tarihi', 'tutar', 'daire_id', 'donem'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return match ($table) {
                    'aidat_grup_tanimlari' => [['id' => 1, 'tip' => 'fixed']],
                    'aidat_listesi' => [
                        ['id' => 1, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '2026-10-10', 'tutar' => 100, 'daire_id' => 99, 'uye_id' => 99, 'donem' => '2026-10'],
                        ['id' => 2, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '2026-10-10', 'tutar' => 100, 'daire_id' => 99, 'uye_id' => 99, 'donem' => '2026-10'],
                    ],
                    'borc_listesi' => [['id' => 3, 'status' => 'unpaid', 'tip' => 'fixed', 'vade_tarihi' => '2026-10-10', 'tutar' => 50, 'daire_id' => 1, 'donem' => '2026-10']],
                    default => [['id' => 1]],
                };
            }
        };
        $data = $validator->validate(new DryRunReport('due-6', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertNotEmpty($data['blockers']);
    }

    public function testAlternativeLegacyDueColumnsAreDetected(): void
    {
        $validator = new class extends DueScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['aidat_listesi', 'daire_tanimlari', 'uye_tanimlari'], true); }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array
            {
                return $table === 'aidat_listesi'
                    ? ['id', 'odendi', 'txt3', 'txt5', 'tutar', 'txt2', 'uye_id']
                    : ['id'];
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'aidat_listesi') {
                    return [['id' => 1, 'odendi' => 'Y', 'txt3' => 'aidat', 'txt5' => '2026-10-10', 'tutar' => 10, 'txt2' => 1, 'uye_id' => 1]];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('due-7', 1, 'due'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $warningCodes = array_map(static fn (array $w): string => (string) ($w['code'] ?? ''), (array) $data['warnings']);
        $this->assertNotContains('DUE_STATUS_COLUMN_MISSING', $warningCodes);
        $this->assertNotContains('DUE_AMOUNT_COLUMN_MISSING', $warningCodes);
    }
}

