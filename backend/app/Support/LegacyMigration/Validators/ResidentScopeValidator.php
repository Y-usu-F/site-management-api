<?php

namespace App\Support\LegacyMigration\Validators;

use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\DryRunReport;
use Config\Database;

class ResidentScopeValidator implements LegacyScopeValidatorInterface
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
            'uye_tanimlari',
            'uye_malik_bilgileri',
            'onceki_kiracilar',
            'uye_fert_tanimlari',
            'uye_arac_bilgisi',
        ];
        foreach ($tables as $table) {
            if (! $this->tableExists($db, $table)) {
                $report->addWarning('RESIDENT_TABLE_MISSING', 'Legacy resident table not found', ['table' => $table]);
                $report->setSourceCount($table, 0);
            } else {
                $report->setSourceCount($table, $this->countRows($db, $table));
            }
        }

        if ($this->tableExists($db, 'uye_tanimlari')) {
            $this->validateResidentsMain($report, $db, $limit);
        }
        if ($this->tableExists($db, 'uye_malik_bilgileri')) {
            $this->validateOccupancyOrphans($report, $db, 'uye_malik_bilgileri', $limit);
        }
        if ($this->tableExists($db, 'onceki_kiracilar')) {
            $this->validateOccupancyOrphans($report, $db, 'onceki_kiracilar', $limit);
            $this->validateOccupancyDates($report, $db, 'onceki_kiracilar', $limit);
        }
        if ($this->tableExists($db, 'uye_fert_tanimlari')) {
            $this->validateHouseholdRelations($report, $db, $limit);
        }
        if ($this->tableExists($db, 'uye_arac_bilgisi')) {
            $this->validateVehiclePlates($report, $db, $limit);
        }

        $report->setTargetCandidateCount('resident_profiles', (int) ($report->toArray()['source_counts']['uye_tanimlari'] ?? 0));
        $report->setTargetCandidateCount('unit_occupancies', (int) (($report->toArray()['source_counts']['uye_malik_bilgileri'] ?? 0) + ($report->toArray()['source_counts']['onceki_kiracilar'] ?? 0)));
        $report->setTargetCandidateCount('resident_vehicles', (int) ($report->toArray()['source_counts']['uye_arac_bilgisi'] ?? 0));

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

    private function validateResidentsMain(DryRunReport $report, $db, int $limit): void
    {
        $table = 'uye_tanimlari';
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id', 'uye_id']);
        $nameColumn = $this->findFirstColumn($columns, ['ad', 'adi', 'first_name', 'isim', 'name', 'txt5']);
        $surnameColumn = $this->findFirstColumn($columns, ['soyad', 'last_name', 'surname', 'txt6']);
        $fullNameColumn = $this->findFirstColumn($columns, ['ad_soyad', 'fullname', 'full_name']);
        $emailColumn = $this->findFirstColumn($columns, ['email', 'eposta', 'e_posta', 'txt11']);
        $phoneColumn = $this->findFirstColumn($columns, ['telefon', 'tel', 'gsm', 'cep_tel', 'txt9', 'txt10']);

        if ($nameColumn === null && $fullNameColumn === null) {
            $report->addWarning('RESIDENT_NAME_COLUMNS_MISSING', 'Resident name/fullname columns are missing', ['table' => $table]);
        }

        $selectColumns = array_values(array_filter([$idColumn, $nameColumn, $surnameColumn, $fullNameColumn, $emailColumn, $phoneColumn]));
        $rows = $this->fetchRows($db, $table, $selectColumns, $limit);
        $identitySeen = [];
        $duplicateIdentityCount = 0;
        $invalidEmailCount = 0;
        $invalidPhoneCount = 0;

        foreach ($rows as $row) {
            $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
            $name = $nameColumn !== null ? trim((string) ($row[$nameColumn] ?? '')) : '';
            $surname = $surnameColumn !== null ? trim((string) ($row[$surnameColumn] ?? '')) : '';
            $fullName = $fullNameColumn !== null ? trim((string) ($row[$fullNameColumn] ?? '')) : '';
            if ($fullName === '' && ($name === '' || $surname === '')) {
                $report->addQuarantineCandidate('resident', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Missing resident identity', ['row' => $row]);
            }

            $email = $emailColumn !== null ? trim((string) ($row[$emailColumn] ?? '')) : '';
            $phone = $phoneColumn !== null ? trim((string) ($row[$phoneColumn] ?? '')) : '';
            $identityKey = strtolower(trim($name . ' ' . $surname . '|' . $email . '|' . preg_replace('/\D+/', '', $phone)));
            if ($identityKey !== '||' && isset($identitySeen[$identityKey])) {
                $duplicateIdentityCount++;
            }
            $identitySeen[$identityKey] = true;

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $invalidEmailCount++;
            }
            if ($phone !== '') {
                $digits = preg_replace('/\D+/', '', $phone);
                if (strlen((string) $digits) < 10) {
                    $invalidPhoneCount++;
                }
            }
        }

        if ($duplicateIdentityCount > 0) {
            $report->addWarning('RESIDENT_DUPLICATE_IDENTITY', 'Duplicate resident identity detected', ['count' => $duplicateIdentityCount]);
        }
        if ($invalidEmailCount > 0) {
            $report->addWarning('RESIDENT_INVALID_EMAIL', 'Malformed email detected', ['count' => $invalidEmailCount]);
        }
        if ($invalidPhoneCount > 0) {
            $report->addWarning('RESIDENT_INVALID_PHONE', 'Invalid/short phone detected', ['count' => $invalidPhoneCount]);
        }
    }

    private function validateOccupancyOrphans(DryRunReport $report, $db, string $table, int $limit): void
    {
        if (! $this->tableExists($db, 'daire_tanimlari')) {
            $report->addWarning('RESIDENT_UNIT_TABLE_MISSING', 'Unit table missing for orphan check', ['table' => $table]);
            return;
        }

        $relColumns = $this->listColumns($db, $table);
        $unitRefColumn = $this->findFirstColumn($relColumns, ['daire_id', 'unit_id', 'daire', 'txt2', 'daire_no']);
        $legacyIdColumn = $this->findFirstColumn($relColumns, ['id']);
        if ($unitRefColumn === null) {
            $report->addWarning('RESIDENT_OCCUPANCY_UNIT_REF_MISSING', 'Occupancy unit reference column missing', ['table' => $table]);
            return;
        }

        $unitColumns = $this->listColumns($db, 'daire_tanimlari');
        $unitIdColumn = $this->findFirstColumn($unitColumns, ['id', 'daire_id']);
        if ($unitIdColumn === null) {
            $report->addWarning('RESIDENT_UNIT_ID_COLUMN_MISSING', 'Unit id column missing', ['table' => 'daire_tanimlari']);
            return;
        }

        $units = $this->fetchRows($db, 'daire_tanimlari', [$unitIdColumn], $limit);
        $unitSet = [];
        foreach ($units as $unit) {
            $k = strtolower(trim((string) ($unit[$unitIdColumn] ?? '')));
            if ($k !== '') {
                $unitSet[$k] = true;
            }
        }

        $rows = $this->fetchRows($db, $table, array_values(array_filter([$legacyIdColumn, $unitRefColumn])), $limit);
        foreach ($rows as $row) {
            $ref = strtolower(trim((string) ($row[$unitRefColumn] ?? '')));
            if ($ref === '' || isset($unitSet[$ref])) {
                continue;
            }
            $legacyId = $legacyIdColumn !== null ? ($row[$legacyIdColumn] ?? null) : null;
            $report->addQuarantineCandidate('occupancy', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Occupancy orphan unit reference', [
                'unit_ref' => $row[$unitRefColumn] ?? null,
            ]);
        }
    }

    private function validateOccupancyDates(DryRunReport $report, $db, string $table, int $limit): void
    {
        $columns = $this->listColumns($db, $table);
        $idColumn = $this->findFirstColumn($columns, ['id']);
        $startDateColumn = $this->findFirstColumn($columns, ['start_date', 'baslangic_tarihi', 'giris_tarihi', 'txt15', 'kayit_tarihi']);
        $endDateColumn = $this->findFirstColumn($columns, ['end_date', 'bitis_tarihi', 'cikis_tarihi', 'degistirme_tarihi']);
        if ($startDateColumn === null && $endDateColumn === null) {
            $report->addWarning('RESIDENT_OCCUPANCY_DATE_COLUMNS_MISSING', 'Occupancy date columns missing', ['table' => $table]);
            return;
        }

        $rows = $this->fetchRows($db, $table, array_values(array_filter([$idColumn, $startDateColumn, $endDateColumn])), $limit);
        $zeroDateCount = 0;
        foreach ($rows as $row) {
            $legacyId = $idColumn !== null ? ($row[$idColumn] ?? null) : null;
            $start = $startDateColumn !== null ? trim((string) ($row[$startDateColumn] ?? '')) : '';
            $end = $endDateColumn !== null ? trim((string) ($row[$endDateColumn] ?? '')) : '';

            if (str_contains($start, '0000-00-00') || str_contains($end, '0000-00-00')) {
                $zeroDateCount++;
            }

            $startTs = $start !== '' && ! str_contains($start, '0000-00-00') ? strtotime($start) : false;
            $endTs = $end !== '' && ! str_contains($end, '0000-00-00') ? strtotime($end) : false;
            if ($startTs !== false && $endTs !== false && $endTs < $startTs) {
                $report->addQuarantineCandidate('occupancy', $table, is_numeric((string) $legacyId) ? (int) $legacyId : $legacyId, 'Invalid occupancy date range', [
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            }
        }
        if ($zeroDateCount > 0) {
            $report->addWarning('RESIDENT_OCCUPANCY_ZERO_DATE', 'Zero-date occupancy values detected', ['count' => $zeroDateCount]);
        }
    }

    private function validateHouseholdRelations(DryRunReport $report, $db, int $limit): void
    {
        $table = 'uye_fert_tanimlari';
        $columns = $this->listColumns($db, $table);
        $relationColumn = $this->findFirstColumn($columns, ['relation_type', 'yakınlık', 'yakinlik', 'fert_tipi', 'txt3', 'txt4']);
        if ($relationColumn === null) {
            $report->addWarning('RESIDENT_HOUSEHOLD_RELATION_COLUMN_MISSING', 'Household relation column missing', ['table' => $table]);
            return;
        }
        $rows = $this->fetchRows($db, $table, [$relationColumn], $limit);
        $allowed = ['spouse', 'child', 'parent', 'sibling', 'dependent', 'es', 'cocuk', 'anne', 'baba', 'kardes'];
        $unsupportedCount = 0;
        foreach ($rows as $row) {
            $val = strtolower(trim((string) ($row[$relationColumn] ?? '')));
            if ($val !== '' && ! in_array($val, $allowed, true)) {
                $unsupportedCount++;
            }
        }
        if ($unsupportedCount > 0) {
            $report->addWarning('RESIDENT_UNSUPPORTED_HOUSEHOLD_RELATION', 'Unsupported household relation types detected', ['count' => $unsupportedCount]);
        }
    }

    private function validateVehiclePlates(DryRunReport $report, $db, int $limit): void
    {
        $table = 'uye_arac_bilgisi';
        $columns = $this->listColumns($db, $table);
        $plateColumn = $this->findFirstColumn($columns, ['plaka', 'plate', 'arac_plaka', 'txt17', 'txt17_2', 'txt17_3', 'txt17_4']);
        if ($plateColumn === null) {
            $report->addWarning('RESIDENT_VEHICLE_PLATE_COLUMN_MISSING', 'Vehicle plate column missing', ['table' => $table]);
            return;
        }
        $rows = $this->fetchRows($db, $table, [$plateColumn], $limit);
        $invalidCount = 0;
        foreach ($rows as $row) {
            $plate = strtoupper(trim((string) ($row[$plateColumn] ?? '')));
            if ($plate === '' || preg_match('/^[0-9]{2}[A-Z]{1,3}[0-9]{2,4}$/', str_replace(' ', '', $plate)) !== 1) {
                $invalidCount++;
            }
        }
        if ($invalidCount > 0) {
            $report->addWarning('RESIDENT_INVALID_VEHICLE_PLATE', 'Invalid or empty vehicle plate detected', ['count' => $invalidCount]);
        }
    }
}

