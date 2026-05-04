<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class EnhanceMigrationQuarantineOperability extends Migration
{
    public function up()
    {
        if (! $this->tableExists('migration_quarantine_logs')) {
            return;
        }

        if (! $this->fieldExists('migration_quarantine_logs', 'run_id')) {
            $this->forge->addColumn('migration_quarantine_logs', [
                'run_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);
        }
        if (! $this->fieldExists('migration_quarantine_logs', 'retry_count')) {
            $this->forge->addColumn('migration_quarantine_logs', [
                'retry_count' => ['type' => 'INT', 'null' => false, 'default' => 0],
            ]);
        }
        if (! $this->fieldExists('migration_quarantine_logs', 'resolved_at')) {
            $this->forge->addColumn('migration_quarantine_logs', [
                'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }
        if (! $this->fieldExists('migration_quarantine_logs', 'resolution_note')) {
            $this->forge->addColumn('migration_quarantine_logs', [
                'resolution_note' => ['type' => 'TEXT', 'null' => true],
            ]);
        }

        $this->createIndexIfPossible('migration_quarantine_logs', 'idx_migration_quarantine_run_id', 'run_id');
        $this->createIndexIfPossible('migration_quarantine_logs', 'idx_migration_quarantine_resolved_at', 'resolved_at');
        $this->createCompositeIndexIfPossible('migration_quarantine_logs', 'idx_migration_quarantine_entity_legacy_table', 'entity_type, legacy_table');
    }

    public function down()
    {
        if (! $this->tableExists('migration_quarantine_logs')) {
            return;
        }

        $this->dropIndexIfPossible('migration_quarantine_logs', 'idx_migration_quarantine_run_id');
        $this->dropIndexIfPossible('migration_quarantine_logs', 'idx_migration_quarantine_resolved_at');
        $this->dropIndexIfPossible('migration_quarantine_logs', 'idx_migration_quarantine_entity_legacy_table');

        if ($this->fieldExists('migration_quarantine_logs', 'run_id')) {
            $this->safeDropColumn('migration_quarantine_logs', 'run_id');
        }
        if ($this->fieldExists('migration_quarantine_logs', 'retry_count')) {
            $this->safeDropColumn('migration_quarantine_logs', 'retry_count');
        }
        if ($this->fieldExists('migration_quarantine_logs', 'resolved_at')) {
            $this->safeDropColumn('migration_quarantine_logs', 'resolved_at');
        }
        if ($this->fieldExists('migration_quarantine_logs', 'resolution_note')) {
            $this->safeDropColumn('migration_quarantine_logs', 'resolution_note');
        }
    }

    private function safeDropColumn(string $table, string $field): void
    {
        try {
            $this->forge->dropColumn($table, $field);
        } catch (Throwable) {
            // Keep rollback idempotent when schema already shifted.
        }
    }

    private function tableExists(string $table): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $row = $this->db->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
                [$table]
            )->getRowArray();
            return is_array($row);
        }
        $row = $this->db->query('SHOW TABLES LIKE ?', [$table])->getRowArray();
        return is_array($row);
    }

    private function fieldExists(string $table, string $field): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA table_info('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $field) {
                    return true;
                }
            }
            return false;
        }
        $row = $this->db->query("SHOW COLUMNS FROM {$table} LIKE ?", [$field])->getRowArray();
        return is_array($row);
    }

    private function createIndexIfPossible(string $table, string $indexName, string $column): void
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$tableName} ({$column})");
        } catch (Throwable) {
        }
    }

    private function createCompositeIndexIfPossible(string $table, string $indexName, string $columns): void
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$tableName} ({$columns})");
        } catch (Throwable) {
        }
    }

    private function dropIndexIfPossible(string $table, string $indexName): void
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$tableName}");
        } catch (Throwable) {
        }
    }
}

