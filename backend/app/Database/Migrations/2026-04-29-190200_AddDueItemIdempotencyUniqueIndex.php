<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddDueItemIdempotencyUniqueIndex extends Migration
{
    public function up()
    {
        if (! $this->isMySql()) {
            return;
        }

        if (! $this->indexExists('due_items', 'uq_due_items_company_unit_definition_period')) {
            $this->db->query(
                'ALTER TABLE `due_items` ADD UNIQUE KEY `uq_due_items_company_unit_definition_period` (`company_id`, `unit_id`, `due_definition_id`, `due_period_id`)'
            );
        }
    }

    public function down()
    {
        if (! $this->isMySql()) {
            return;
        }

        if ($this->indexExists('due_items', 'uq_due_items_company_unit_definition_period')) {
            try {
                $this->db->query('ALTER TABLE `due_items` DROP INDEX `uq_due_items_company_unit_definition_period`');
            } catch (Throwable) {
                // Ignore when index is already absent.
            }
        }
    }

    private function isMySql(): bool
    {
        return in_array($this->db->DBDriver, ['MySQLi', 'PDO'], true);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $databaseName = method_exists($this->db, 'getDatabase')
            ? (string) $this->db->getDatabase()
            : '';
        $row = $this->db->table('information_schema.statistics')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->get(1)
            ->getRowArray();

        return $row !== null;
    }
}
