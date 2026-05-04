<?php

namespace App\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\DryRunReport;
use Config\Database;

class DueScopeValidator implements LegacyScopeValidatorInterface
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
            'aidat_grup_tanimlari',
            'aidat_listesi',
            'borc_listesi',
            'aidat_iade_listesi',
            'borc_iade_listesi',
            'gecikme_faiz_oranlari',
        ];
        foreach ($tables as $table) {
            if (! $this->tableExists($db, $table)) {
                $report->addWarning('DUE_TABLE_MISSING', 'Legacy due table not found', ['table' => $table]);
                $report->setSourceCount($table, 0);
            } else {
                $report->setSourceCount($table, $this->countRows($db, $table));
            }
        }

        if ($this->tableExists($db, 'aidat_grup_tanimlari')) {
            $this->validateDefinitionTypes($report, $db, $limit);
        }

        $unitExists = $this->tableExists($db, 'daire_tanimlari');
        $residentExists = $this->tableExists($db, 'uye_tanimlari');
        $unitSet = $unitExists ? $this->loadIdSet($db, 'daire_tanimlari', ['id', 'daire_id'], $limit) : [];
        $residentSet = $residentExists ? $this->loadIdSet($db, 'uye_tanimlari', ['id', 'uye_id'], $limit) : [];

        $nonRefundTables = ['aidat_listesi', 'borc_listesi'];
        foreach (['aidat_listesi', 'borc_listesi', 'aidat_iade_listesi', 'borc_iade_listesi'] as $table) {
            if (! $this->tableExists($db, $table)) {
                continue;
            }
            $this->validateDueItemsByTable(
                $report,
                $db,
                $table,
                in_array($table, $nonRefundTables, true),
                $unitSet,
                $residentSet,
                $limit
            );
        }

        if ($this->tableExists($db, 'gecikme_faiz_oranlari')) {
            $this->validateInterestRates($report, $db, $limit);
        }

        $sourceCounts = (array) $report->toArray()['source_counts'];
        $report->setTargetCandidateCount('due_definitions', (int) ($sourceCounts['aidat_grup_tanimlari'] ?? 0));
        $report->setTargetCandidateCount('due_items', (int) (($sourceCounts['aidat_listesi'] ?? 0) + ($sourceCounts['borc_listesi'] ?? 0)));

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

    private function validateDefinitionTypes(DryRunReport $report, $db, int $limit): void
    {
        $table = 'aidat_grup_tanimlari';
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id']);
        $typeColumn = $this->findFirstColumn($columns, ['calculation_type', 'hesaplama_tipi', 'tip', 'aidat_tipi', 'type', 'txt1', 'txt2', 'txt3']);
        if ($typeColumn === null) {
            $report->addWarning('DUE_DEFINITION_TYPE_COLUMN_MISSING', 'Due definition type column missing', ['table' => $table]);
            return;
        }
        $rows = $this->fetchRows($db, $table, array_values(array_filter([$idColumn, $typeColumn])), $limit);
        $allowed = ['fixed', 'area_based', 'manual', 'sabit', 'metrekare', 'manuel'];
        foreach ($rows as $row) {
            $raw = $this->normalize((string) ($row[$typeColumn] ?? ''));
            if ($raw === '' || ! in_array($raw, $allowed, true)) {
                $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
                $report->addQuarantineCandidate('due_definition', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown due definition type', ['type' => $row[$typeColumn] ?? null]);
            }
        }
    }

    /**
     * @param array<string,bool> $unitSet
     * @param array<string,bool> $residentSet
     */
    private function validateDueItemsByTable(DryRunReport $report, $db, string $table, bool $isNonRefund, array $unitSet, array $residentSet, int $limit): void
    {
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id']);
        $statusColumn = $this->findFirstColumn($columns, ['status', 'durum', 'durumu', 'odendi']);
        $typeColumn = $this->findFirstColumn($columns, ['type', 'tip', 'borc_tipi', 'txt3', 'aidat_grubu']);
        $dueDateColumn = $this->findFirstColumn($columns, ['due_date', 'vade_tarihi', 'vade', 'txt5', 'txt4', 'islem_tarihi']);
        $amountColumn = $this->findFirstColumn($columns, ['amount', 'tutar', 'borc_tutari', 'iade_tutar', 'odenen_tutar']);
        $unitRefColumn = $this->findFirstColumn($columns, ['daire_id', 'unit_id', 'txt2']);
        $residentRefColumn = $this->findFirstColumn($columns, ['uye_id', 'resident_id', 'uye']);
        $periodColumn = $this->findFirstColumn($columns, ['period_key', 'donem', 'yil_ay', 'txt2', 'txt1', 'ay']);

        if ($statusColumn === null) {
            $report->addWarning('DUE_STATUS_COLUMN_MISSING', 'Due status column missing', ['table' => $table]);
        }
        if ($amountColumn === null) {
            $report->addWarning('DUE_AMOUNT_COLUMN_MISSING', 'Due amount column missing', ['table' => $table]);
        }

        $selectColumns = array_values(array_filter([$idColumn, $statusColumn, $typeColumn, $dueDateColumn, $amountColumn, $unitRefColumn, $residentRefColumn, $periodColumn]));
        $rows = $this->fetchRows($db, $table, $selectColumns, $limit);

        $allowedStatus = ['unpaid', 'partial', 'paid', 'cancelled', 'odenmedi', 'kismi', 'odendi', 'iptal'];
        $allowedTypes = ['fixed', 'area_based', 'manual', 'sabit', 'metrekare', 'manuel', 'aidat', 'borc'];
        $seenNatural = [];

        foreach ($rows as $row) {
            $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
            if ($statusColumn !== null) {
                $status = $this->normalize((string) ($row[$statusColumn] ?? ''));
                if ($status === '' || ! in_array($status, $allowedStatus, true)) {
                    $report->addQuarantineCandidate('due_item', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown due item status', ['status' => $row[$statusColumn] ?? null]);
                }
            }
            if ($typeColumn !== null) {
                $type = $this->normalize((string) ($row[$typeColumn] ?? ''));
                if ($type === '' || ! in_array($type, $allowedTypes, true)) {
                    $report->addQuarantineCandidate('due_item', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Unknown due item type', ['type' => $row[$typeColumn] ?? null]);
                }
            }

            if ($dueDateColumn !== null) {
                $dueDate = trim((string) ($row[$dueDateColumn] ?? ''));
                if (str_contains($dueDate, '0000-00-00')) {
                    $report->addWarning('DUE_ZERO_DATE', 'Zero-date due date detected', ['table' => $table]);
                } elseif ($dueDate === '' || strtotime($dueDate) === false) {
                    $report->addQuarantineCandidate('due_item', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Missing/unparseable due date', ['due_date' => $dueDate]);
                }
            }

            if ($amountColumn !== null) {
                $rawAmount = $row[$amountColumn] ?? null;
                if ($rawAmount === null || $rawAmount === '' || ! is_numeric((string) $rawAmount)) {
                    $report->addQuarantineCandidate('due_item', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Non-numeric amount', ['amount' => $rawAmount]);
                } elseif ($isNonRefund && (float) $rawAmount < 0) {
                    $report->addBlocker('DUE_NEGATIVE_NON_REFUND_AMOUNT', 'Negative amount in non-refund due table', [
                        'table' => $table,
                        'legacy_id' => $legacyId,
                        'amount' => $rawAmount,
                    ]);
                }
            }

            if ($unitRefColumn !== null) {
                $unitRef = $this->normalize((string) ($row[$unitRefColumn] ?? ''));
                if ($unitRef !== '' && $unitSet !== [] && ! isset($unitSet[$unitRef])) {
                    $report->addQuarantineCandidate('due_item', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan unit reference', ['unit_ref' => $row[$unitRefColumn] ?? null]);
                }
            }
            if ($residentRefColumn !== null) {
                $residentRef = $this->normalize((string) ($row[$residentRefColumn] ?? ''));
                if ($residentRef !== '' && $residentSet !== [] && ! isset($residentSet[$residentRef])) {
                    $report->addQuarantineCandidate('due_item', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan resident reference', ['resident_ref' => $row[$residentRefColumn] ?? null]);
                }
            }

            $naturalKey = implode('|', [
                $this->normalize((string) ($row[$unitRefColumn] ?? '')),
                $this->normalize((string) ($row[$periodColumn] ?? '')),
                $this->normalize((string) ($row[$typeColumn] ?? '')),
                $this->normalize((string) ($row[$amountColumn] ?? '')),
            ]);
            if ($naturalKey !== '|||' && isset($seenNatural[$naturalKey])) {
                $report->addBlocker('DUE_DUPLICATE_NATURAL_KEY', 'Duplicate due natural key detected', [
                    'table' => $table,
                    'natural_key' => $naturalKey,
                ]);
            }
            $seenNatural[$naturalKey] = true;
        }
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

    private function validateInterestRates(DryRunReport $report, $db, int $limit): void
    {
        $table = 'gecikme_faiz_oranlari';
        $columns = $this->listColumns($db, $table);
        $rateColumn = $this->findFirstColumn($columns, ['faiz_orani', 'oran', 'rate']);
        if ($rateColumn === null) {
            $report->addWarning('DUE_INTEREST_RATE_COLUMN_MISSING', 'Interest rate column missing', ['table' => $table]);
            $report->addWarning('DUE_INTEREST_DERIVATION_UNSAFE', 'Interest/penalty derivation is unsafe due to missing rate column', ['table' => $table]);
            return;
        }
        $rows = $this->fetchRows($db, $table, [$rateColumn], $limit);
        $badCount = 0;
        foreach ($rows as $row) {
            $rate = $row[$rateColumn] ?? null;
            if ($rate === null || $rate === '' || ! is_numeric((string) $rate) || (float) $rate < 0) {
                $badCount++;
            }
        }
        if ($badCount > 0) {
            $report->addWarning('DUE_INTEREST_RATE_INVALID', 'Interest rate is non-numeric or negative', ['count' => $badCount]);
        }
    }
}

