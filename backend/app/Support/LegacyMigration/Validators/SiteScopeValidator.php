<?php

namespace App\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\DryRunReport;
use Config\Database;

class SiteScopeValidator implements LegacyScopeValidatorInterface
{
    /**
     * @param array<string,string> $options
     */
    public function validate(DryRunReport $report, array $options): DryRunReport
    {
        $legacyConnection = (string) ($options['legacy-connection'] ?? 'default');
        $limit = max(1, (int) ($options['limit'] ?? '1000'));
        $db = $this->getLegacyConnection($legacyConnection);

        $blocksTable = 'blok_tanimlari';
        $unitsTable = 'daire_tanimlari';

        if (! $this->tableExists($db, $blocksTable)) {
            $report->addWarning('SITE_BLOCK_TABLE_MISSING', 'Legacy block table not found', ['table' => $blocksTable]);
            $report->setSourceCount($blocksTable, 0);
        }
        if (! $this->tableExists($db, $unitsTable)) {
            $report->addWarning('SITE_UNIT_TABLE_MISSING', 'Legacy unit table not found', ['table' => $unitsTable]);
            $report->setSourceCount($unitsTable, 0);
        }
        if (! $this->tableExists($db, $blocksTable) || ! $this->tableExists($db, $unitsTable)) {
            return $report;
        }

        $blockColumns = $this->listColumns($db, $blocksTable);
        $unitColumns = $this->listColumns($db, $unitsTable);

        $report->setSourceCount($blocksTable, $this->countRows($db, $blocksTable));
        $report->setSourceCount($unitsTable, $this->countRows($db, $unitsTable));

        $blockIdColumn = $this->findFirstColumn($blockColumns, ['id', 'blok_id']);
        $blockNameColumn = $this->findFirstColumn($blockColumns, ['blok_adi', 'blok_ad', 'blokadi', 'name', 'ad', 'txt1']);
        $blockCodeColumn = $this->findFirstColumn($blockColumns, ['blok_kodu', 'blok_kod', 'code', 'kod', 'txt2']);
        if ($blockNameColumn === null && $blockCodeColumn === null) {
            $report->addWarning('SITE_BLOCK_IDENTITY_COLUMNS_MISSING', 'Block name/code columns are missing', ['table' => $blocksTable]);
        } else {
            $this->validateDuplicateBlocks($report, $db, $blocksTable, $blockNameColumn, $blockCodeColumn, $limit);
        }

        $unitIdColumn = $this->findFirstColumn($unitColumns, ['id', 'daire_id']);
        $unitNoColumn = $this->findFirstColumn($unitColumns, ['daire_no', 'unit_no', 'bagimsiz_bolum_no', 'daireNo', 'no', 'txt2']);
        $unitBlockRefColumn = $this->findFirstColumn($unitColumns, ['blok_id', 'blok_tanim_id', 'block_id', 'blok', 'txt1']);
        $netAreaColumn = $this->findFirstColumn($unitColumns, ['net_m2', 'net_alan', 'net_metre', 'txt6']);
        $grossAreaColumn = $this->findFirstColumn($unitColumns, ['gross_m2', 'brut_m2', 'brut_alan', 'brut_metre', 'txt5']);

        if ($unitNoColumn === null) {
            $report->addWarning('SITE_UNIT_NO_COLUMN_MISSING', 'Unit number column is missing', ['table' => $unitsTable]);
        }
        if ($unitBlockRefColumn === null) {
            $report->addWarning('SITE_UNIT_BLOCK_REF_MISSING', 'Unit block reference column is missing', ['table' => $unitsTable]);
        }

        $selectColumns = array_values(array_filter([$unitIdColumn, $unitNoColumn, $unitBlockRefColumn, $netAreaColumn, $grossAreaColumn]));
        if ($selectColumns === []) {
            return $report;
        }
        $unitRows = $this->fetchRows($db, $unitsTable, $selectColumns, $limit);

        $blockIdSet = $this->buildBlockIdentitySet($db, $blocksTable, [$blockIdColumn, $blockNameColumn, $blockCodeColumn], $limit);
        $seenUnitKeys = [];
        $duplicateUnitCount = 0;
        $missingUnitNoCount = 0;
        $invalidAreaCount = 0;
        $orphanBlockCount = 0;

        foreach ($unitRows as $row) {
            $legacyId = $unitIdColumn !== null ? ($row[$unitIdColumn] ?? null) : null;
            $unitNo = $unitNoColumn !== null ? $this->normalizeKey((string) ($row[$unitNoColumn] ?? '')) : '';
            $blockRef = $unitBlockRefColumn !== null ? $this->normalizeKey((string) ($row[$unitBlockRefColumn] ?? '')) : '';

            if ($unitNoColumn !== null && $unitNo === '') {
                $missingUnitNoCount++;
                $report->addQuarantineCandidate('unit', $unitsTable, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Missing unit number', [
                    'row' => $row,
                ]);
            }

            if ($unitNoColumn !== null && $unitBlockRefColumn !== null && $unitNo !== '' && $blockRef !== '') {
                $key = $blockRef . '|' . $unitNo;
                if (isset($seenUnitKeys[$key])) {
                    $duplicateUnitCount++;
                }
                $seenUnitKeys[$key] = true;
            }

            if ($unitBlockRefColumn !== null && $blockRef !== '' && $blockIdSet !== [] && ! isset($blockIdSet[$blockRef])) {
                $orphanBlockCount++;
                $report->addQuarantineCandidate('unit', $unitsTable, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Orphan block reference', [
                    'block_ref' => $row[$unitBlockRefColumn] ?? null,
                ]);
            }

            foreach ([$netAreaColumn, $grossAreaColumn] as $areaColumn) {
                if ($areaColumn === null) {
                    continue;
                }
                $rawValue = $row[$areaColumn] ?? null;
                if ($rawValue === null || $rawValue === '') {
                    continue;
                }
                if (! is_numeric((string) $rawValue) || (float) $rawValue < 0) {
                    $invalidAreaCount++;
                    break;
                }
            }
        }

        if ($duplicateUnitCount > 0) {
            $report->addBlocker('SITE_DUPLICATE_UNIT_NO_PER_BLOCK', 'Duplicate unit number detected under same block reference', [
                'count' => $duplicateUnitCount,
            ]);
        }
        if ($invalidAreaCount > 0) {
            $report->addWarning('SITE_INVALID_AREA_VALUE', 'Invalid or negative unit area detected', [
                'count' => $invalidAreaCount,
            ]);
        }
        if ($missingUnitNoCount > 0) {
            $report->addWarning('SITE_MISSING_UNIT_NO', 'Missing unit number rows detected', [
                'count' => $missingUnitNoCount,
            ]);
        }
        if ($orphanBlockCount > 0) {
            $report->addWarning('SITE_ORPHAN_BLOCK_REF', 'Unit rows with orphan block reference detected', [
                'count' => $orphanBlockCount,
            ]);
        }

        $report->setTargetCandidateCount('blocks', $this->countRows($db, $blocksTable));
        $report->setTargetCandidateCount('units', $this->countRows($db, $unitsTable));
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

    private function normalizeKey(string $value): string
    {
        return strtolower(trim($value));
    }

    protected function validateDuplicateBlocks(DryRunReport $report, $db, string $table, ?string $nameColumn, ?string $codeColumn, int $limit): void
    {
        $selectColumns = array_values(array_filter([$nameColumn, $codeColumn]));
        $rows = $this->fetchRows($db, $table, $selectColumns, $limit);
        $seen = [];
        $duplicateCount = 0;
        foreach ($rows as $row) {
            foreach ($selectColumns as $column) {
                $key = $this->normalizeKey((string) ($row[$column] ?? ''));
                if ($key === '') {
                    continue;
                }
                $cacheKey = $column . '|' . $key;
                if (isset($seen[$cacheKey])) {
                    $duplicateCount++;
                }
                $seen[$cacheKey] = true;
            }
        }
        if ($duplicateCount > 0) {
            $report->addBlocker('SITE_DUPLICATE_BLOCK_NAME_OR_CODE', 'Duplicate normalized block name/code detected', [
                'count' => $duplicateCount,
            ]);
        }
    }

    /**
     * @return array<string,bool>
     */
    protected function buildBlockIdentitySet($db, string $table, array $identityColumns, int $limit): array
    {
        $identityColumns = array_values(array_filter($identityColumns, static fn ($column): bool => $column !== null && $column !== ''));
        if ($identityColumns === []) {
            return [];
        }
        $rows = $this->fetchRows($db, $table, $identityColumns, $limit);
        $set = [];
        foreach ($rows as $row) {
            foreach ($identityColumns as $column) {
                $key = $this->normalizeKey((string) ($row[$column] ?? ''));
                if ($key !== '') {
                    $set[$key] = true;
                }
            }
        }
        return $set;
    }
}

