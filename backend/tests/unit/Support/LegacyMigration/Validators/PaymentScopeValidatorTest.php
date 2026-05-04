<?php

namespace Tests\Unit\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\DryRunReport;
use App\Support\LegacyMigration\Validators\PaymentScopeValidator;
use CodeIgniter\Test\CIUnitTestCase;

final class PaymentScopeValidatorTest extends CIUnitTestCase
{
    public function testMissingTableColumnsWarningNoCrash(): void
    {
        $validator = new class extends PaymentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return false; }
        };
        $data = $validator->validate(new DryRunReport('pay-1', 1, 'payment'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
    }

    public function testSourceCountsSetWhenTablesExist(): void
    {
        $validator = new class extends PaymentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return true; }
            protected function countRows($db, string $table): int { return 3; }
            protected function listColumns($db, string $table): array { return ['id', 'method', 'status', 'amount', 'payment_date', 'daire_id', 'uye_id', 'aidat_id', 'payment_no']; }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return [['id' => 1, 'method' => 'cash', 'status' => 'completed', 'amount' => 100, 'payment_date' => '2026-10-10', 'daire_id' => 1, 'uye_id' => 1, 'aidat_id' => 1, 'payment_no' => 'P1']];
            }
        };
        $data = $validator->validate(new DryRunReport('pay-2', 1, 'payment'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertSame(3, (int) ($data['source_counts']['tahsilat_listesi'] ?? 0));
    }

    public function testUnknownMethodStatusCreatesQuarantineCandidate(): void
    {
        $validator = new class extends PaymentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['tahsilat_listesi', 'daire_tanimlari', 'uye_tanimlari', 'aidat_listesi', 'uye_cari_hareket'], true); }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'tahsilat_listesi' => ['id', 'method', 'status', 'amount', 'payment_date', 'daire_id', 'uye_id', 'aidat_id', 'payment_no'],
                    'uye_cari_hareket' => ['amount'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'tahsilat_listesi') {
                    return [['id' => 1, 'method' => 'x', 'status' => 'y', 'amount' => 100, 'payment_date' => '2026-10-10', 'daire_id' => 1, 'uye_id' => 1, 'aidat_id' => 1, 'payment_no' => '']];
                }
                if ($table === 'uye_cari_hareket') {
                    return [['amount' => 100]];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('pay-3', 1, 'payment'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
    }

    public function testNonNumericAmountAndNegativeNonRefund(): void
    {
        $validator = new class extends PaymentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool { return in_array($table, ['tahsilat_listesi', 'odeme_listesi', 'daire_tanimlari', 'uye_tanimlari', 'aidat_listesi', 'uye_cari_hareket'], true); }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'tahsilat_listesi', 'odeme_listesi' => ['id', 'method', 'status', 'amount', 'payment_date', 'daire_id', 'uye_id', 'aidat_id', 'payment_no'],
                    'uye_cari_hareket' => ['amount'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'tahsilat_listesi') {
                    return [['id' => 1, 'method' => 'cash', 'status' => 'completed', 'amount' => 'x', 'payment_date' => '2026-10-10', 'daire_id' => 1, 'uye_id' => 1, 'aidat_id' => 1, 'payment_no' => '']];
                }
                if ($table === 'odeme_listesi') {
                    return [['id' => 2, 'method' => 'cash', 'status' => 'completed', 'amount' => -5, 'payment_date' => '2026-10-10', 'daire_id' => 1, 'uye_id' => 1, 'aidat_id' => 1, 'payment_no' => '']];
                }
                if ($table === 'uye_cari_hareket') {
                    return [['amount' => 0]];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('pay-4', 1, 'payment'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertNotEmpty($data['blockers']);
    }

    public function testInvalidDateAndOrphanAndDuplicateAndAllocationMismatch(): void
    {
        $validator = new class extends PaymentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool
            {
                return in_array($table, ['tahsilat_listesi', 'uye_online_odemeler', 'bankadan_gelen_veriler', 'daire_tanimlari', 'uye_tanimlari', 'aidat_listesi', 'uye_cari_hareket'], true);
            }
            protected function countRows($db, string $table): int { return 2; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'tahsilat_listesi' => ['id', 'method', 'status', 'amount', 'payment_date', 'daire_id', 'uye_id', 'aidat_id', 'payment_no'],
                    'uye_online_odemeler' => ['id', 'provider_txn_id', 'provider_status'],
                    'bankadan_gelen_veriler' => ['amount', 'date', 'reference'],
                    'uye_cari_hareket' => ['amount'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                return match ($table) {
                    'tahsilat_listesi' => [
                        ['id' => 1, 'method' => 'cash', 'status' => 'completed', 'amount' => 100, 'payment_date' => '0000-00-00', 'daire_id' => 99, 'uye_id' => 99, 'aidat_id' => 99, 'payment_no' => ''],
                        ['id' => 2, 'method' => 'cash', 'status' => 'completed', 'amount' => 100, 'payment_date' => '0000-00-00', 'daire_id' => 99, 'uye_id' => 99, 'aidat_id' => 99, 'payment_no' => ''],
                    ],
                    'uye_online_odemeler' => [['id' => 3, 'provider_txn_id' => '', 'provider_status' => 'x']],
                    'bankadan_gelen_veriler' => [['amount' => 12, 'date' => 'bad-date', 'reference' => '']],
                    'uye_cari_hareket' => [['amount' => 0]],
                    default => [['id' => 1]],
                };
            }
        };
        $data = $validator->validate(new DryRunReport('pay-5', 1, 'payment'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $this->assertNotEmpty($data['warnings']);
        $this->assertNotEmpty($data['quarantine_candidates']);
        $this->assertNotEmpty($data['blockers']);
    }

    public function testAlternativeLegacyPaymentColumnsAreDetected(): void
    {
        $validator = new class extends PaymentScopeValidator {
            protected function getLegacyConnection(string $group) { return (object) ['DBDriver' => 'MySQLi']; }
            protected function tableExists($db, string $table): bool
            {
                return in_array($table, ['tahsilat_listesi', 'daire_tanimlari', 'uye_tanimlari', 'aidat_listesi', 'uye_cari_hareket'], true);
            }
            protected function countRows($db, string $table): int { return 1; }
            protected function listColumns($db, string $table): array
            {
                return match ($table) {
                    'tahsilat_listesi' => ['id', 'txt7', 'txt3', 'tutar', 'txt1', 'uye_id', 'aidat_id', 'makbuz_no'],
                    'uye_cari_hareket' => ['tutar'],
                    default => ['id'],
                };
            }
            protected function fetchRows($db, string $table, array $columns, int $limit): array
            {
                if ($table === 'tahsilat_listesi') {
                    return [['id' => 1, 'txt7' => 'Kredi Kartı', 'txt3' => 'Aidat', 'tutar' => 100, 'txt1' => '2026-10-10', 'uye_id' => 1, 'aidat_id' => 1, 'makbuz_no' => 'M1']];
                }
                if ($table === 'uye_cari_hareket') {
                    return [['tutar' => 100]];
                }
                return [['id' => 1]];
            }
        };
        $data = $validator->validate(new DryRunReport('pay-6', 1, 'payment'), ['legacy-connection' => 'legacy', 'limit' => '100'])->toArray();
        $warningCodes = array_map(static fn (array $w): string => (string) ($w['code'] ?? ''), (array) $data['warnings']);
        $this->assertNotContains('PAYMENT_METHOD_COLUMN_MISSING', $warningCodes);
        $this->assertNotContains('PAYMENT_AMOUNT_COLUMN_MISSING', $warningCodes);
    }
}

