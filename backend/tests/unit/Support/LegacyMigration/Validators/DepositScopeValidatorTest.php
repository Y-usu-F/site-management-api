<?php

namespace Tests\Unit\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\DryRunReport;
use App\Support\LegacyMigration\Validators\DepositScopeValidator;
use CodeIgniter\Test\CIUnitTestCase;

final class DepositScopeValidatorTest extends CIUnitTestCase
{
    public function testMissingTableColumnsWarningNoCrash(): void
    {
        $validator = new class extends DepositScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return false; }
        };
        $data = $validator->validate(new DryRunReport('dep-1', 1, 'deposit'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
    }

    public function testSourceCountSetWhenTableExists(): void
    {
        $validator = new class extends DepositScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function countRows($db, string $table): int { return 4; }
            protected function listColumns($db, string $table): array { return ['id', 'status', 'transaction_type', 'amount', 'balance', 'deposit_date', 'daire_id', 'uye_id', 'deposit_no']; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'depozito_listesi') {
                    return [['id' => 1, 'status' => 'active', 'transaction_type' => 'receive', 'amount' => 100, 'balance' => 100, 'deposit_date' => '2026-01-01', 'daire_id' => 1, 'uye_id' => 1, 'deposit_no' => 'D-1']];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('dep-2', 1, 'deposit'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertSame(4, (int) ($data['source_counts']['depozito_listesi'] ?? 0));
    }

    public function testUnknownStatusTypeCreatesQuarantineCandidate(): void
    {
        $validator = new class extends DepositScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'depozito_listesi' => ['id', 'status', 'transaction_type', 'amount', 'balance', 'deposit_date', 'daire_id', 'uye_id', 'deposit_no'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'depozito_listesi') {
                    return [['id' => 10, 'status' => 'x', 'transaction_type' => 'y', 'amount' => 100, 'balance' => 50, 'deposit_date' => '2026-01-01', 'daire_id' => 1, 'uye_id' => 1, 'deposit_no' => '']];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('dep-3', 1, 'deposit'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
    }

    public function testNonNumericAmountBalanceAndNegativeInvalidAmountCreatesBlocker(): void
    {
        $validator = new class extends DepositScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function countRows($db, string $table): int { return 2; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'depozito_listesi' => ['id', 'status', 'transaction_type', 'amount', 'balance', 'deposit_date', 'daire_id', 'uye_id', 'deposit_no'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'depozito_listesi') {
                    return [
                        ['id' => 1, 'status' => 'active', 'transaction_type' => 'receive', 'amount' => 'x', 'balance' => 'y', 'deposit_date' => '2026-01-01', 'daire_id' => 1, 'uye_id' => 1, 'deposit_no' => ''],
                        ['id' => 2, 'status' => 'active', 'transaction_type' => 'receive', 'amount' => -10, 'balance' => -5, 'deposit_date' => '2026-01-02', 'daire_id' => 1, 'uye_id' => 1, 'deposit_no' => ''],
                    ];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('dep-4', 1, 'deposit'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertNotEmpty($data['blockers']);
    }

    public function testInvalidDateOrphanMissingReasonDuplicateNaturalKey(): void
    {
        $validator = new class extends DepositScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function countRows($db, string $table): int { return 2; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'depozito_listesi' => ['id', 'status', 'transaction_type', 'amount', 'balance', 'deposit_date', 'daire_id', 'uye_id', 'refund_reason_code', 'deduction_reason_code', 'deposit_no'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'depozito_listesi') {
                    return [
                        ['id' => 1, 'status' => 'partially_refunded', 'transaction_type' => 'refund', 'amount' => 100, 'balance' => 80, 'deposit_date' => '0000-00-00', 'daire_id' => 99, 'uye_id' => 99, 'refund_reason_code' => '', 'deduction_reason_code' => '', 'deposit_no' => ''],
                        ['id' => 2, 'status' => 'active', 'transaction_type' => 'receive', 'amount' => 100, 'balance' => 100, 'deposit_date' => '0000-00-00', 'daire_id' => 99, 'uye_id' => 99, 'refund_reason_code' => '', 'deduction_reason_code' => '', 'deposit_no' => ''],
                    ];
                }
                // only id=1 exists, so 99 is orphan
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('dep-5', 1, 'deposit'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertNotEmpty($data['blockers']);
    }
}

