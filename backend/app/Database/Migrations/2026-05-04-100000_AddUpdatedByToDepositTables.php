<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * TenantAwareModel injects updated_by on insert/update after allowed-field protection.
 * Deposit tables originally only had created_by; align schema so inserts do not 500.
 */
class AddUpdatedByToDepositTables extends Migration
{
    public function up()
    {
        foreach (['deposits', 'deposit_transactions'] as $table) {
            if (! $this->tableExists($table) || $this->fieldExists($table, 'updated_by')) {
                continue;
            }

            $this->forge->addColumn($table, [
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        foreach (['deposits', 'deposit_transactions'] as $table) {
            if (! $this->tableExists($table) || ! $this->fieldExists($table, 'updated_by')) {
                continue;
            }

            $this->forge->dropColumn($table, 'updated_by');
        }
    }

    private function tableExists(string $table): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $row = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table])->getRowArray();

            return is_array($row);
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $row = $this->db->table('information_schema.tables')
            ->select('TABLE_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->get(1)
            ->getRowArray();

        return is_array($row);
    }

    private function fieldExists(string $table, string $field): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA table_info('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $field) {
                    return true;
                }
            }

            return false;
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $row = $this->db->table('information_schema.columns')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $field)
            ->get(1)
            ->getRowArray();

        return is_array($row);
    }
}
