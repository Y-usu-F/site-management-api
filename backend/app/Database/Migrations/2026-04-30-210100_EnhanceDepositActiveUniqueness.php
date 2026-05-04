<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class EnhanceDepositActiveUniqueness extends Migration
{
    public function up()
    {
        if (! $this->tableExists('deposits')) {
            return;
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $table = $prefix . 'deposits';

        if (! $this->fieldExists('deposits', 'active_scope_key')) {
            $this->safeQuery("ALTER TABLE {$table} ADD COLUMN active_scope_key VARCHAR(16) GENERATED ALWAYS AS (CASE WHEN status = 'active' AND deleted_at IS NULL THEN 'active' ELSE NULL END) STORED");
        }
        if (! $this->indexExists('deposits', 'uq_deposits_active_unit_resident')) {
            $this->safeQuery("CREATE UNIQUE INDEX uq_deposits_active_unit_resident ON {$table} (company_id, unit_id, resident_profile_id, active_scope_key)");
        }
    }

    public function down()
    {
        if (! $this->tableExists('deposits')) {
            return;
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $table = $prefix . 'deposits';

        if ($this->indexExists('deposits', 'uq_deposits_active_unit_resident')) {
            $this->safeQuery("DROP INDEX uq_deposits_active_unit_resident ON {$table}");
        }
        if ($this->fieldExists('deposits', 'active_scope_key')) {
            $this->safeQuery("ALTER TABLE {$table} DROP COLUMN active_scope_key");
        }
    }

    private function safeQuery(string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (Throwable) {
            // Keep migration idempotent across reruns.
        }
    }

    private function tableExists(string $table): bool
    {
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
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
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $row = $this->db->table('information_schema.columns')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $field)
            ->get(1)
            ->getRowArray();

        return is_array($row);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $row = $this->db->table('information_schema.statistics')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->get(1)
            ->getRowArray();

        return is_array($row);
    }
}
