<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddResidentManagementUniqueIndexes extends Migration
{
    public function up()
    {
        if (! $this->isMySql()) {
            return;
        }

        $this->addUniqueIfMissing(
            'unit_occupancies',
            'uq_unit_resident_rel_active',
            '`company_id`, `unit_id`, `resident_profile_id`, `relationship_type`, `status`'
        );

        $this->addUniqueIfMissing(
            'unit_occupancies',
            'uq_unit_primary_owner',
            '`company_id`, `unit_id`, `relationship_type`, `is_primary`, `status`'
        );

        $this->addUniqueIfMissing(
            'unit_occupancies',
            'uq_unit_primary_tenant',
            '`company_id`, `unit_id`, `relationship_type`, `is_primary`, `status`'
        );

        $this->addUniqueIfMissing(
            'resident_vehicles',
            'uq_vehicle_plate_active',
            '`company_id`, `plate_number`, `status`'
        );
    }

    public function down()
    {
        if (! $this->isMySql()) {
            return;
        }

        $this->dropIndexIfExists('unit_occupancies', 'uq_unit_resident_rel_active');
        $this->dropIndexIfExists('unit_occupancies', 'uq_unit_primary_owner');
        $this->dropIndexIfExists('unit_occupancies', 'uq_unit_primary_tenant');
        $this->dropIndexIfExists('resident_vehicles', 'uq_vehicle_plate_active');
    }

    private function isMySql(): bool
    {
        return in_array($this->db->DBDriver, ['MySQLi', 'PDO'], true);
    }

    private function addUniqueIfMissing(string $table, string $indexName, string $columnsSql): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $sql = sprintf(
            'ALTER TABLE `%s` ADD UNIQUE KEY `%s` (%s)',
            $table,
            $indexName,
            $columnsSql
        );
        $this->db->query($sql);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        $sql = sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName);
        try {
            $this->db->query($sql);
        } catch (Throwable) {
            // Ignore when index is already absent.
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $builder = $this->db->table('information_schema.statistics');
        $row = $builder
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $this->db->database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->get(1)
            ->getRowArray();

        return $row !== null;
    }
}
