<?php

namespace App\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\DryRunReport;
use Config\Database;

class DepositScopeValidator implements LegacyScopeValidatorInterface
{
    /**
     * @param array<string,string> $options
     */
    public function validate(DryRunReport $report, array $options): DryRunReport
    {
        $legacyConnection = (string) ($options['legacy-connection'] ?? 'default');
        $limit = max(1, (int) ($options['limit'] ?? '1000'));
        $db = $this->getLegacyConnection($legacyConnection);
        $table = 'depozito_listesi';

        if (! $this->tableExists($db, $table)) {
            $report->addWarning('DEPOSIT_TABLE_MISSING', 'Legacy deposit table not found', ['table' => $table]);
            $report->setSourceCount($table, 0);
            return $report;
        }

        $report->setSourceCount($table, $this->countRows($db, $table));
        $unitSet = $this->tableExists($db, 'daire_tanimlari') ? $this->loadIdSet($db, 'daire_tanimlari', ['id', 'daire_id'], $limit) : [];
        $residentSet = $this->tableExists($db, 'uye_tanimlari') ? $this->loadIdSet($db, 'uye_tanimlari', ['id', 'uye_id'], $limit) : [];

        $this->validateDepositRows($report, $db, $table, $unitSet, $residentSet, $limit);
        $report->setTargetCandidateCount('deposits', (int) ($report->toArray()['source_counts'][$table] ?? 0));
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
     */
    private function validateDepositRows(DryRunReport $report, $db, string $table, array $unitSet, array $residentSet, int $limit): void
    {
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id']);
        $statusColumn = $this->findFirstColumn($columns, ['status', 'durum']);
        $typeColumn = $this->findFirstColumn($columns, ['transaction_type', 'islem_tipi', 'tip']);
        $amountColumn = $this->findFirstColumn($columns, ['amount', 'tutar']);
        $balanceColumn = $this->findFirstColumn($columns, ['balance', 'bakiye']);
        $dateColumn = $this->findFirstColumn($columns, ['deposit_date', 'tarih', 'islem_tarihi']);
        $unitRefColumn = $this->findFirstColumn($columns, ['daire_id', 'unit_id']);
        $residentRefColumn = $this->findFirstColumn($columns, ['uye_id', 'resident_id']);
        $refundReasonColumn = $this->findFirstColumn($columns, ['refund_reason_code', 'iade_neden_kodu']);
        $deductionReasonColumn = $this->findFirstColumn($columns, ['deduction_reason_code', 'kesinti_neden_kodu']);
        $refNoColumn = $this->findFirstColumn($columns, ['deposit_no', 'referans_no', 'islem_no']);

        if ($statusColumn === null) {
            $report->addWarning('DEPOSIT_STATUS_COLUMN_MISSING', 'Deposit status column missing', ['table' => $table]);
        }
        if ($typeColumn === null) {
            $report->addWarning('DEPOSIT_TX_TYPE_COLUMN_MISSING', 'Deposit transaction type column missing', ['table' => $table]);
        }
        if ($amountColumn === null) {
            $report->addWarning('DEPOSIT_AMOUNT_COLUMN_MISSING', 'Deposit amount column missing', ['table' => $table]);
        }
        if ($dateColumn === null) {
            $report->addWarning('DEPOSIT_DATE_COLUMN_MISSING', 'Deposit date column missing', ['table' => $table]);
        }

        $select = array_values(array_filter([
            $idColumn,
            $statusColumn,
            $typeColumn,
            $amountColumn,
            $balanceColumn,
            $dateColumn,
            $unitRefColumn,
            $residentRefColumn,
            $refundReasonColumn,
            $deductionReasonColumn,
            $refNoColumn,
        ]));
        $rows = $this->fetchRows($db, $table, $select, $limit);

        $allowedStatus = ['active', 'partially_refunded', 'refunded', 'applied_to_debt', 'cancelled'];
        $allowedType = ['receive', 'refund', 'deduction', 'apply_to_debt', 'cancel'];
        $seenNatural = [];

        foreach ($rows as $row) {
            $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
            $status = $statusColumn !== null ? $this->normalize((string) ($row[$statusColumn] ?? '')) : '';
            $type = $typeColumn !== null ? $this->normalize((string) ($row[$typeColumn] ?? '')) : '';
            $amountRaw = $amountColumn !== null ? ($row[$amountColumn] ?? null) : null;
            $balanceRaw = $balanceColumn !== null ? ($row[$balanceColumn] ?? null) : null;
            $dateRaw = $dateColumn !== null ? trim((string) ($row[$dateColumn] ?? '')) : '';

            if ($statusColumn !== null && ($status === '' || ! in_array($status, $allowedStatus, true))) {
                $report->addQuarantineCandidate('deposit', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown deposit status', ['status' => $row[$statusColumn] ?? null]);
            }
            if ($typeColumn !== null && ($type === '' || ! in_array($type, $allowedType, true))) {
                $report->addQuarantineCandidate('deposit_transaction', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown deposit transaction type', ['transaction_type' => $row[$typeColumn] ?? null]);
            }

            if ($amountColumn !== null && ($amountRaw === null || $amountRaw === '' || ! is_numeric((string) $amountRaw))) {
                $report->addQuarantineCandidate('deposit', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Non-numeric deposit amount', ['amount' => $amountRaw]);
            }
            if ($balanceColumn !== null && $balanceRaw !== null && $balanceRaw !== '' && ! is_numeric((string) $balanceRaw)) {
                $report->addQuarantineCandidate('deposit', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Non-numeric deposit balance', ['balance' => $balanceRaw]);
            }

            $isRefundOrDeductionContext = in_array($type, ['refund', 'deduction'], true) || in_array($status, ['refunded', 'partially_refunded'], true);
            if ($amountColumn !== null && is_numeric((string) $amountRaw) && (float) $amountRaw < 0 && ! $isRefundOrDeductionContext) {
                $report->addBlocker('DEPOSIT_NEGATIVE_INVALID_AMOUNT', 'Negative deposit amount without refund/deduction context', ['legacy_id' => $legacyId]);
            }
            if ($balanceColumn !== null && is_numeric((string) $balanceRaw) && (float) $balanceRaw < 0 && ! $isRefundOrDeductionContext) {
                $report->addBlocker('DEPOSIT_NEGATIVE_INVALID_BALANCE', 'Negative calculated balance without refund/deduction context', ['legacy_id' => $legacyId]);
            }

            if ($dateColumn !== null) {
                if (str_contains($dateRaw, '0000-00-00')) {
                    $report->addWarning('DEPOSIT_ZERO_DATE', 'Zero-date deposit date detected', ['table' => $table]);
                } elseif ($dateRaw === '' || strtotime($dateRaw) === false) {
                    $report->addQuarantineCandidate('deposit', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Missing/unparseable deposit date', ['deposit_date' => $dateRaw]);
                }
            }

            if ($unitRefColumn !== null) {
                $unitRef = $this->normalize((string) ($row[$unitRefColumn] ?? ''));
                if ($unitRef !== '' && $unitSet !== [] && ! isset($unitSet[$unitRef])) {
                    $report->addQuarantineCandidate('deposit', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan unit reference', ['unit_ref' => $row[$unitRefColumn] ?? null]);
                }
            }
            if ($residentRefColumn !== null) {
                $residentRef = $this->normalize((string) ($row[$residentRefColumn] ?? ''));
                if ($residentRef !== '' && $residentSet !== [] && ! isset($residentSet[$residentRef])) {
                    $report->addQuarantineCandidate('deposit', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan resident reference', ['resident_ref' => $row[$residentRefColumn] ?? null]);
                }
            }

            if ($isRefundOrDeductionContext) {
                $refundReason = trim((string) ($row[$refundReasonColumn] ?? ''));
                $deductionReason = trim((string) ($row[$deductionReasonColumn] ?? ''));
                if ($refundReason === '' && $deductionReason === '') {
                    $report->addWarning('DEPOSIT_REASON_CODE_MISSING', 'Refund/deduction exists but reason code could not be derived', ['legacy_id' => $legacyId]);
                }
            }

            $naturalKey = implode('|', [
                $this->normalize((string) ($row[$residentRefColumn] ?? '')),
                $this->normalize((string) ($row[$unitRefColumn] ?? '')),
                $this->normalize($dateRaw),
                $this->normalize((string) ($amountRaw ?? '')),
            ]);
            $uniqueRef = $this->normalize((string) ($row[$refNoColumn] ?? ''));
            if ($naturalKey !== '|||' && $uniqueRef === '' && isset($seenNatural[$naturalKey])) {
                $report->addBlocker('DEPOSIT_DUPLICATE_NATURAL_KEY', 'Duplicate deposit natural key without unique reference', ['table' => $table]);
            }
            $seenNatural[$naturalKey] = true;
        }
    }
}

