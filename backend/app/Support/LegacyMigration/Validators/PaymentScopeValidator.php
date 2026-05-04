<?php

namespace App\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\DryRunReport;
use Config\Database;

class PaymentScopeValidator implements LegacyScopeValidatorInterface
{
    /**
     * @param array<string,string> $options
     */
    public function validate(DryRunReport $report, array $options): DryRunReport
    {
        $legacyConnection = (string) ($options['legacy-connection'] ?? 'default');
        $limit = max(1, (int) ($options['limit'] ?? '1000'));
        $db = $this->getLegacyConnection($legacyConnection);

        $tables = [
            'tahsilat_listesi',
            'odeme_listesi',
            'uye_online_odemeler',
            'bankadan_gelen_veriler',
            'uye_cari_hareket',
            'uye_cari_hareket_fazla_para',
        ];
        foreach ($tables as $table) {
            if (! $this->tableExists($db, $table)) {
                $report->addWarning('PAYMENT_TABLE_MISSING', 'Legacy payment table not found', ['table' => $table]);
                $report->setSourceCount($table, 0);
            } else {
                $report->setSourceCount($table, $this->countRows($db, $table));
            }
        }

        $unitSet = $this->tableExists($db, 'daire_tanimlari') ? $this->loadIdSet($db, 'daire_tanimlari', ['id', 'daire_id'], $limit) : [];
        $residentSet = $this->tableExists($db, 'uye_tanimlari') ? $this->loadIdSet($db, 'uye_tanimlari', ['id', 'uye_id'], $limit) : [];
        $dueSet = $this->tableExists($db, 'aidat_listesi') ? $this->loadIdSet($db, 'aidat_listesi', ['id'], $limit) : [];

        foreach (['tahsilat_listesi', 'odeme_listesi'] as $table) {
            if ($this->tableExists($db, $table)) {
                $this->validatePaymentRows($report, $db, $table, $unitSet, $residentSet, $dueSet, $limit);
            }
        }

        if ($this->tableExists($db, 'uye_online_odemeler')) {
            $this->validateOnlinePayments($report, $db, $limit);
        }
        if ($this->tableExists($db, 'bankadan_gelen_veriler')) {
            $this->validateBankReconciliationCandidates($report, $db, $limit);
        }

        $source = (array) $report->toArray()['source_counts'];
        $report->setTargetCandidateCount('payments', (int) (($source['tahsilat_listesi'] ?? 0) + ($source['odeme_listesi'] ?? 0)));
        $report->setTargetCandidateCount('payment_events', (int) ($source['uye_online_odemeler'] ?? 0));
        return $report;
    }

    protected function getLegacyConnection(string $group)
    {
        return Database::connect($group);
    }

    protected function tableExists($db, string $table): bool
    {
        if (($db->DBDriver ?? '') === 'SQLite3') {
            $row = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table])->getRowArray();
            return is_array($row);
        }
        $row = $db->query('SHOW TABLES LIKE ?', [$table])->getRowArray();
        return is_array($row);
    }

    /**
     * @return list<string>
     */
    protected function listColumns($db, string $table): array
    {
        if (($db->DBDriver ?? '') === 'SQLite3') {
            $rows = $db->query("PRAGMA table_info('{$table}')")->getResultArray();
            return array_values(array_map(static fn (array $row): string => (string) ($row['name'] ?? ''), $rows));
        }
        $rows = $db->query("SHOW COLUMNS FROM {$table}")->getResultArray();
        return array_values(array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $rows));
    }

    protected function countRows($db, string $table): int
    {
        $row = $db->query("SELECT COUNT(*) AS cnt FROM {$table}")->getRowArray();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * @param list<string> $columns
     * @return list<array<string,mixed>>
     */
    protected function fetchRows($db, string $table, array $columns, int $limit): array
    {
        if ($columns === []) {
            return [];
        }
        return $db->table($table)->select(implode(',', $columns))->limit($limit)->get()->getResultArray();
    }

    /**
     * @param list<string> $columns
     * @param list<string> $candidates
     */
    protected function findFirstColumn(array $columns, array $candidates): ?string
    {
        $lookup = array_fill_keys(array_map('strtolower', $columns), true);
        foreach ($candidates as $candidate) {
            if (isset($lookup[strtolower($candidate)])) {
                return $candidate;
            }
        }
        return null;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * @return array<string,bool>
     */
    private function loadIdSet($db, string $table, array $candidateColumns, int $limit): array
    {
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, $candidateColumns);
        if ($idColumn === null) {
            return [];
        }
        $rows = $this->fetchRows($db, $table, [$idColumn], $limit);
        $set = [];
        foreach ($rows as $row) {
            $k = $this->normalize((string) ($row[$idColumn] ?? ''));
            if ($k !== '') {
                $set[$k] = true;
            }
        }
        return $set;
    }

    /**
     * @param array<string,bool> $unitSet
     * @param array<string,bool> $residentSet
     * @param array<string,bool> $dueSet
     */
    private function validatePaymentRows(DryRunReport $report, $db, string $table, array $unitSet, array $residentSet, array $dueSet, int $limit): void
    {
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id']);
        $methodColumn = $this->findFirstColumn($columns, ['method', 'odeme_tipi', 'odeme_sekli', 'txt7', 'txt5']);
        $statusColumn = $this->findFirstColumn($columns, ['status', 'durum', 'odendi', 'txt3']);
        $amountColumn = $this->findFirstColumn($columns, ['amount', 'tutar', 'miktar']);
        $dateColumn = $this->findFirstColumn($columns, ['payment_date', 'odeme_tarihi', 'tarih', 'txt1', 'islem_tarihi']);
        $unitRefColumn = $this->findFirstColumn($columns, ['daire_id', 'unit_id', 'txt2']);
        $residentRefColumn = $this->findFirstColumn($columns, ['uye_id', 'resident_id']);
        $dueRefColumn = $this->findFirstColumn($columns, ['aidat_id', 'borc_id', 'due_item_id']);
        $refNoColumn = $this->findFirstColumn($columns, ['payment_no', 'referans_no', 'islem_no', 'makbuz_no', 'ref_no']);

        if ($methodColumn === null) {
            $report->addWarning('PAYMENT_METHOD_COLUMN_MISSING', 'Payment method column missing', ['table' => $table]);
        }
        if ($statusColumn === null) {
            $report->addWarning('PAYMENT_STATUS_COLUMN_MISSING', 'Payment status column missing', ['table' => $table]);
        }
        if ($amountColumn === null) {
            $report->addWarning('PAYMENT_AMOUNT_COLUMN_MISSING', 'Payment amount column missing', ['table' => $table]);
        }

        $select = array_values(array_filter([$idColumn, $methodColumn, $statusColumn, $amountColumn, $dateColumn, $unitRefColumn, $residentRefColumn, $dueRefColumn, $refNoColumn]));
        $rows = $this->fetchRows($db, $table, $select, $limit);
        $seenNatural = [];

        $allowedMethod = ['cash', 'bank_transfer', 'credit_card', 'online', 'nakit', 'havale', 'banka', 'kredi_karti'];
        $allowedStatus = ['completed', 'pending', 'cancelled', 'refunded', 'tamamlandi', 'bekliyor', 'iptal', 'iade'];
        foreach ($rows as $row) {
            $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
            $method = $methodColumn !== null ? $this->normalize((string) ($row[$methodColumn] ?? '')) : '';
            $status = $statusColumn !== null ? $this->normalize((string) ($row[$statusColumn] ?? '')) : '';
            $amountRaw = $amountColumn !== null ? ($row[$amountColumn] ?? null) : null;
            $dateRaw = $dateColumn !== null ? trim((string) ($row[$dateColumn] ?? '')) : '';

            if ($methodColumn !== null && ($method === '' || ! in_array($method, $allowedMethod, true))) {
                $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown payment method', ['method' => $row[$methodColumn] ?? null]);
            }
            if ($statusColumn !== null && ($status === '' || ! in_array($status, $allowedStatus, true))) {
                $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown payment status', ['status' => $row[$statusColumn] ?? null]);
            }

            if ($amountColumn !== null) {
                if ($amountRaw === null || $amountRaw === '' || ! is_numeric((string) $amountRaw)) {
                    $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Non-numeric payment amount', ['amount' => $amountRaw]);
                } elseif ((float) $amountRaw < 0 && ! str_contains($table, 'iade') && ! in_array($status, ['refunded', 'iade'], true)) {
                    $report->addBlocker('PAYMENT_NEGATIVE_NON_REFUND_AMOUNT', 'Negative amount in non-refund payment row', ['table' => $table, 'legacy_id' => $legacyId]);
                }
            }

            if ($dateColumn !== null) {
                if (str_contains($dateRaw, '0000-00-00')) {
                    $report->addWarning('PAYMENT_ZERO_DATE', 'Zero-date payment date detected', ['table' => $table]);
                } elseif ($dateRaw === '' || strtotime($dateRaw) === false) {
                    $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Missing/unparseable payment date', ['payment_date' => $dateRaw]);
                }
            }

            if ($unitRefColumn !== null) {
                $unitRef = $this->normalize((string) ($row[$unitRefColumn] ?? ''));
                if ($unitRef !== '' && $unitSet !== [] && ! isset($unitSet[$unitRef])) {
                    $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan unit reference', ['unit_ref' => $row[$unitRefColumn] ?? null]);
                }
            }
            if ($residentRefColumn !== null) {
                $residentRef = $this->normalize((string) ($row[$residentRefColumn] ?? ''));
                if ($residentRef !== '' && $residentSet !== [] && ! isset($residentSet[$residentRef])) {
                    $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan resident reference', ['resident_ref' => $row[$residentRefColumn] ?? null]);
                }
            }
            if ($dueRefColumn !== null) {
                $dueRef = $this->normalize((string) ($row[$dueRefColumn] ?? ''));
                if ($dueRef !== '' && $dueSet !== [] && ! isset($dueSet[$dueRef])) {
                    $report->addQuarantineCandidate('payment', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan due reference', ['due_ref' => $row[$dueRefColumn] ?? null]);
                }
            }

            $naturalKey = implode('|', [
                $this->normalize((string) ($row[$residentRefColumn] ?? '')),
                $this->normalize((string) ($row[$unitRefColumn] ?? '')),
                $this->normalize($dateRaw),
                $this->normalize((string) ($amountRaw ?? '')),
                $method,
            ]);
            $uniqueRef = $this->normalize((string) ($row[$refNoColumn] ?? ''));
            if ($naturalKey !== '||||' && $uniqueRef === '' && isset($seenNatural[$naturalKey])) {
                $report->addBlocker('PAYMENT_DUPLICATE_NATURAL_KEY', 'Duplicate payment natural key without unique reference', ['table' => $table]);
            }
            $seenNatural[$naturalKey] = true;
        }

        $this->validateAllocationMismatch($report, $db, $table, $amountColumn, $limit);
    }

    private function validateAllocationMismatch(DryRunReport $report, $db, string $table, ?string $amountColumn, int $limit): void
    {
        if ($amountColumn === null) {
            return;
        }
        if (! $this->tableExists($db, 'uye_cari_hareket')) {
            $report->addWarning('PAYMENT_ALLOCATION_SOURCE_MISSING', 'Cari movement table missing for allocation mismatch check', ['table' => $table]);
            return;
        }
        $cariColumns = $this->listColumns($db, 'uye_cari_hareket');
        $cariAmountColumn = $this->findFirstColumn($cariColumns, ['amount', 'tutar']);
        if ($cariAmountColumn === null) {
            $report->addWarning('PAYMENT_CARI_AMOUNT_COLUMN_MISSING', 'Cari amount column missing for mismatch check', []);
            return;
        }

        $paymentRows = $this->fetchRows($db, $table, [$amountColumn], $limit);
        $cariRows = $this->fetchRows($db, 'uye_cari_hareket', [$cariAmountColumn], $limit);
        $paymentTotal = 0.0;
        foreach ($paymentRows as $r) {
            if (is_numeric((string) ($r[$amountColumn] ?? null))) {
                $paymentTotal += (float) $r[$amountColumn];
            }
        }
        $cariTotal = 0.0;
        foreach ($cariRows as $r) {
            if (is_numeric((string) ($r[$cariAmountColumn] ?? null))) {
                $cariTotal += (float) $r[$cariAmountColumn];
            }
        }
        if (abs($paymentTotal - $cariTotal) > 0.01) {
            $report->addBlocker('PAYMENT_ALLOCATION_MISMATCH', 'Payment total and allocation/cari movement total mismatch', [
                'payment_total' => round($paymentTotal, 2),
                'cari_total' => round($cariTotal, 2),
            ]);
        }
    }

    private function validateOnlinePayments(DryRunReport $report, $db, int $limit): void
    {
        $table = 'uye_online_odemeler';
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id']);
        $providerTxnColumn = $this->findFirstColumn($columns, ['provider_txn_id', 'transaction_id', 'islem_no', 'referans_no']);
        $providerStatusColumn = $this->findFirstColumn($columns, ['provider_status', 'status', 'durum']);
        if ($providerTxnColumn === null) {
            $report->addWarning('PAYMENT_PROVIDER_TXN_COLUMN_MISSING', 'Provider transaction id column missing', ['table' => $table]);
            return;
        }
        $rows = $this->fetchRows($db, $table, array_values(array_filter([$idColumn, $providerTxnColumn, $providerStatusColumn])), $limit);
        $allowed = ['ok', 'success', 'completed', 'failed', 'pending', 'cancelled'];
        foreach ($rows as $row) {
            $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
            $txn = trim((string) ($row[$providerTxnColumn] ?? ''));
            if ($txn === '') {
                $report->addWarning('PAYMENT_PROVIDER_TXN_ID_MISSING', 'Provider transaction id is missing', ['legacy_id' => $legacyId]);
            }
            if ($providerStatusColumn !== null) {
                $ps = $this->normalize((string) ($row[$providerStatusColumn] ?? ''));
                if ($ps !== '' && ! in_array($ps, $allowed, true)) {
                    $report->addQuarantineCandidate('payment_event', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Provider status cannot be mapped safely', ['provider_status' => $row[$providerStatusColumn] ?? null]);
                }
            }
        }
    }

    private function validateBankReconciliationCandidates(DryRunReport $report, $db, int $limit): void
    {
        $table = 'bankadan_gelen_veriler';
        $columns = $this->listColumns($db, $table);
        $amountColumn = $this->findFirstColumn($columns, ['amount', 'tutar']);
        $dateColumn = $this->findFirstColumn($columns, ['date', 'tarih', 'islem_tarihi']);
        $refColumn = $this->findFirstColumn($columns, ['reference', 'referans_no', 'aciklama']);
        if ($amountColumn === null || $dateColumn === null) {
            $report->addWarning('PAYMENT_BANK_RECON_COLUMNS_MISSING', 'Bank reconciliation columns missing', ['table' => $table]);
            return;
        }
        $rows = $this->fetchRows($db, $table, array_values(array_filter([$amountColumn, $dateColumn, $refColumn])), $limit);
        foreach ($rows as $row) {
            $amount = $row[$amountColumn] ?? null;
            $date = trim((string) ($row[$dateColumn] ?? ''));
            $ref = trim((string) ($row[$refColumn] ?? ''));
            if (! is_numeric((string) $amount) || $date === '' || strtotime($date) === false) {
                $report->addWarning('PAYMENT_BANK_ROW_CONFLICT', 'Bank row has amount/date/reference conflict', ['row' => $row]);
                continue;
            }
            if ($ref === '') {
                $report->addWarning('PAYMENT_BANK_UNMATCHED', 'Bank row cannot be matched to payment candidate', ['row' => $row]);
            }
        }
    }
}

