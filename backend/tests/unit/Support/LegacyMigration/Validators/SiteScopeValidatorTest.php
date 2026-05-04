<?php

namespace Tests\Unit\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\DryRunReport;
use App\Support\LegacyMigration\Validators\SiteScopeValidator;
use CodeIgniter\Test\CIUnitTestCase;

final class SiteScopeValidatorTest extends CIUnitTestCase
{
    public function testMissingLegacyTablesProducesWarningNotCrash(): void
    {
        $validator = new class extends SiteScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return false; }
        };

        $report = new DryRunReport('run-a', 1, 'site');
        $result = $validator->validate($report, ['legacy-connection' => 'legacy', 'limit' => '100']);
        $data = $result->toArray();

        $this->assertNotEmpty($data['warnings']);
        $this->assertSame('REVIEW', $data['go_no_go_status']);
    }

    public function testValidatorSetsSourceCountsWhenTablesExist(): void
    {
        $validator = new class extends SiteScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function listColumns($db, string $table): array
            {
                return $table === 'blok_tanimlari' ? ['id', 'blok_adi', 'blok_kodu'] : ['id', 'daire_no', 'blok_id'];
            }
            protected function countRows($db, string $table): int { return $table === 'blok_tanimlari' ? 2 : 3; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'blok_tanimlari') {
                    return [['id' => 1, 'blok_adi' => 'A', 'blok_kodu' => 'A'], ['id' => 2, 'blok_adi' => 'B', 'blok_kodu' => 'B']];
                }
                return [['id' => 1, 'daire_no' => '1', 'blok_id' => '1'], ['id' => 2, 'daire_no' => '2', 'blok_id' => '1'], ['id' => 3, 'daire_no' => '3', 'blok_id' => '2']];
            }
        };

        $result = $validator->validate(new DryRunReport('run-b', 2, 'site'), ['legacy-connection' => 'legacy', 'limit' => '100']);
        $data = $result->toArray();
        $this->assertSame(2, (int) ($data['source_counts']['blok_tanimlari'] ?? 0));
        $this->assertSame(3, (int) ($data['source_counts']['daire_tanimlari'] ?? 0));
    }

    public function testMissingUnitNumberCreatesQuarantineCandidate(): void
    {
        $validator = new class extends SiteScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function listColumns($db, string $table): array
            {
                return $table === 'blok_tanimlari' ? ['id', 'blok_adi'] : ['id', 'daire_no', 'blok_id'];
            }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'blok_tanimlari') {
                    return [['id' => 10, 'blok_adi' => 'A']];
                }
                return [['id' => 20, 'daire_no' => '', 'blok_id' => '10']];
            }
        };

        $result = $validator->validate(new DryRunReport('run-c', 3, 'site'), ['legacy-connection' => 'legacy', 'limit' => '100']);
        $data = $result->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertSame('REVIEW', $data['go_no_go_status']);
    }

    public function testBlockerChangesStatusToNoGo(): void
    {
        $validator = new class extends SiteScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function listColumns($db, string $table): array
            {
                return $table === 'blok_tanimlari' ? ['id', 'blok_adi', 'blok_kodu'] : ['id', 'daire_no', 'blok_id'];
            }
            protected function countRows($db, string $table): int { return 2; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'blok_tanimlari') {
                    return [['id' => 1, 'blok_adi' => 'A', 'blok_kodu' => 'X'], ['id' => 2, 'blok_adi' => 'A', 'blok_kodu' => 'Y']];
                }
                return [['id' => 11, 'daire_no' => '1', 'blok_id' => '1'], ['id' => 12, 'daire_no' => '1', 'blok_id' => '1']];
            }
        };

        $result = $validator->validate(new DryRunReport('run-d', 4, 'site'), ['legacy-connection' => 'legacy', 'limit' => '100']);
        $this->assertSame('NO_GO', (string) ($result->toArray()['go_no_go_status'] ?? ''));
    }

    public function testAlternativeLegacyColumnsReduceMissingWarnings(): void
    {
        $validator = new class extends SiteScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function listColumns($db, string $table): array
            {
                return $table === 'blok_tanimlari' ? ['id', 'txt1'] : ['id', 'txt1', 'txt2', 'txt5', 'txt6'];
            }
            protected function countRows($db, string $table): int { return 1; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return $table === 'blok_tanimlari'
                    ? [['id' => 1, 'txt1' => 'A Blok']]
                    : [['id' => 2, 'txt1' => 'A Blok', 'txt2' => 10, 'txt5' => 120, 'txt6' => 100]];
            }
        };

        $data = $validator->validate(new DryRunReport('run-e', 5, 'site'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $warningCodes = array_map(static fn (array $w): string => (string) ($w['code'] ?? ''), (array) $data['warnings']);
        $this->assertNotContains('SITE_BLOCK_IDENTITY_COLUMNS_MISSING', $warningCodes);
        $this->assertNotContains('SITE_UNIT_NO_COLUMN_MISSING', $warningCodes);
        $this->assertNotContains('SITE_UNIT_BLOCK_REF_MISSING', $warningCodes);
    }
}

