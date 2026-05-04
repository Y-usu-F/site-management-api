<?php

namespace Tests\Unit\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\DryRunReport;
use App\Support\LegacyMigration\Validators\ResidentScopeValidator;
use CodeIgniter\Test\CIUnitTestCase;

final class ResidentScopeValidatorTest extends CIUnitTestCase
{
    public function testMissingTableOrColumnsProducesWarningNoCrash(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return $table === 'uye_tanimlari'; }
            protected function listColumns($db, string $table): array { return ['id']; }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array { return [['id' => 1]]; }
        };

        $result = $validator->validate(new DryRunReport('run-r1', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100']);
        $this->assertNotEmpty($result->toArray()['warnings']);
    }

    public function testSourceCountsSetWhenTablesExist(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function listColumns($db, string $table): array { return ['id', 'ad', 'soyad', 'email', 'telefon']; }
            protected function countRows($db, string $table): int { return 5; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array { return [['id' => 1, 'ad' => 'A', 'soyad' => 'B']]; }
        };

        $data = $validator->validate(new DryRunReport('run-r2', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertSame(5, (int) ($data['source_counts']['uye_tanimlari'] ?? 0));
        $this->assertSame(5, (int) ($data['source_counts']['uye_malik_bilgileri'] ?? 0));
    }

    public function testMissingIdentityCreatesQuarantineCandidate(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return $table === 'uye_tanimlari'; }
            protected function listColumns($db, string $table): array { return ['id', 'ad', 'soyad', 'email', 'telefon']; }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array { return [['id' => 1, 'ad' => '', 'soyad' => '', 'email' => 'x@y.com', 'telefon' => '05551234567']]; }
        };

        $data = $validator->validate(new DryRunReport('run-r3', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
    }

    public function testInvalidEmailPhoneCreatesWarning(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return $table === 'uye_tanimlari'; }
            protected function listColumns($db, string $table): array { return ['id', 'ad', 'soyad', 'email', 'telefon']; }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array { return [['id' => 1, 'ad' => 'Ali', 'soyad' => 'Veli', 'email' => 'badmail', 'telefon' => '123']]; }
        };

        $data = $validator->validate(new DryRunReport('run-r4', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
    }

    public function testOrphanUnitCreatesQuarantineCandidate(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['uye_malik_bilgileri', 'daire_tanimlari'], true); }
            protected function listColumns($db, string $table): array
            {
                return $table === 'daire_tanimlari' ? ['id'] : ['id', 'daire_id'];
            }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return $table === 'daire_tanimlari' ? [['id' => 10]] : [['id' => 77, 'daire_id' => 999]];
            }
        };

        $data = $validator->validate(new DryRunReport('run-r5', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
    }

    public function testInvalidDateRangeCreatesQuarantineCandidate(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['onceki_kiracilar', 'daire_tanimlari'], true); }
            protected function listColumns($db, string $table): array
            {
                if ($table === 'daire_tanimlari') { return ['id']; }
                return ['id', 'daire_id', 'start_date', 'end_date'];
            }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'daire_tanimlari') { return [['id' => 1]]; }
                return [['id' => 88, 'daire_id' => 1, 'start_date' => '2025-12-01', 'end_date' => '2025-01-01']];
            }
        };

        $data = $validator->validate(new DryRunReport('run-r6', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
    }

    public function testAlternativeLegacyNameEmailPhoneColumnsAreDetected(): void
    {
        $validator = new class extends ResidentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return $table === 'uye_tanimlari'; }
            protected function listColumns($db, string $table): array { return ['id', 'txt5', 'txt6', 'txt11', 'txt9']; }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return [['id' => 1, 'txt5' => 'Ali', 'txt6' => 'Veli', 'txt11' => 'ali@example.com', 'txt9' => '05551234567']];
            }
        };
        $data = $validator->validate(new DryRunReport('run-r7', 1, 'resident'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $warningCodes = array_map(static fn (array $w): string => (string) ($w['code'] ?? ''), (array) $data['warnings']);
        $this->assertNotContains('RESIDENT_NAME_COLUMNS_MISSING', $warningCodes);
    }
}

