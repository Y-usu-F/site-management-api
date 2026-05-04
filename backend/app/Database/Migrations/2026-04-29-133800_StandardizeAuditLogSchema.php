<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class StandardizeAuditLogSchema extends Migration
{
    public function up()
    {
        if (! $this->tableExists('audit_logs')) {
            return;
        }

        $this->safeAddColumnIfMissing('audit_logs', 'ip_address', [
            'type' => 'VARCHAR',
            'constraint' => 45,
            'null' => true,
        ]);
        $this->safeAddColumnIfMissing('audit_logs', 'request_id', [
            'type' => 'VARCHAR',
            'constraint' => 64,
            'null' => true,
        ]);
        $this->safeAddColumnIfMissing('audit_logs', 'occurred_at', [
            'type' => 'DATETIME',
            'null' => true,
        ]);
        $this->safeAddColumnIfMissing('audit_logs', 'old_values', [
            'type' => 'JSON',
            'null' => true,
        ]);
        $this->safeAddColumnIfMissing('audit_logs', 'new_values', [
            'type' => 'JSON',
            'null' => true,
        ]);

        if ($this->fieldExists('audit_logs', 'request_id')) {
            $this->createIndexIfMissing('audit_logs', 'idx_audit_logs_request_id', 'request_id');
        }
        if ($this->fieldExists('audit_logs', 'occurred_at')) {
            $this->createIndexIfMissing('audit_logs', 'idx_audit_logs_occurred_at', 'occurred_at');
        }
        if ($this->fieldExists('audit_logs', 'action')) {
            $this->createIndexIfMissing('audit_logs', 'idx_audit_logs_action', 'action');
        }
    }

    public function down()
    {
        if (! $this->tableExists('audit_logs')) {
            return;
        }

        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_action');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_occurred_at');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_request_id');

        foreach (['new_values', 'old_values', 'occurred_at', 'request_id', 'ip_address'] as $column) {
            if ($this->fieldExists('audit_logs', $column)) {
                $this->safeDropColumn('audit_logs', $column);
            }
        }
    }

    private function safeAddColumnIfMissing(string $table, string $column, array $definition): void
    {
        if ($this->fieldExists($table, $column)) {
            return;
        }

        try {
            $this->forge->addColumn($table, [$column => $definition]);
        } catch (Throwable) {
            // Keep migration idempotent across partial schemas.
        }
    }

    private function createIndexIfMissing(string $table, string $indexName, string $column): void
    {
        try {
            $result = $this->db
                ->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")
                ->getResultArray();

            if ($result === []) {
                $this->db->query("CREATE INDEX {$indexName} ON {$table} ({$column})");
            }
        } catch (Throwable) {
            // Skip index creation when target column does not exist yet.
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            $result = $this->db
                ->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")
                ->getResultArray();

            if ($result !== []) {
                $this->db->query("DROP INDEX {$indexName} ON {$table}");
            }
        } catch (Throwable) {
            // Ignore to keep rollback resilient across partial schemas.
        }
    }

    private function safeDropColumn(string $table, string $column): void
    {
        try {
            $this->forge->dropColumn($table, $column);
        } catch (Throwable) {
            // Ignore to keep rollback resilient across partial schemas.
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
}
