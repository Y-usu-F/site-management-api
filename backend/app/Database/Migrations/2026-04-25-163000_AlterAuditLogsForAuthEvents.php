<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AlterAuditLogsForAuthEvents extends Migration
{
    public function up()
    {
        if (! $this->tableExists('audit_logs')) {
            return;
        }

        $fields = [
            'event' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'action',
            ],
            'actor_user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'event',
            ],
            'target_user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'actor_user_id',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'target_user_id',
            ],
            'ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'after' => 'status',
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'ip',
            ],
            'meta' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'new_data',
            ],
        ];

        $additions = [];
        foreach ($fields as $name => $definition) {
            if (! $this->fieldExists('audit_logs', $name)) {
                $additions[$name] = $definition;
            }
        }
        if ($additions !== []) {
            $this->forge->addColumn('audit_logs', $additions);
        }

        if ($this->fieldExists('audit_logs', 'event') && ! $this->indexExists('audit_logs', 'idx_audit_logs_event')) {
            $this->safeQuery('CREATE INDEX idx_audit_logs_event ON audit_logs (event)');
        }
        if ($this->fieldExists('audit_logs', 'actor_user_id') && ! $this->indexExists('audit_logs', 'idx_audit_logs_actor_user_id')) {
            $this->safeQuery('CREATE INDEX idx_audit_logs_actor_user_id ON audit_logs (actor_user_id)');
        }
        if ($this->fieldExists('audit_logs', 'target_user_id') && ! $this->indexExists('audit_logs', 'idx_audit_logs_target_user_id')) {
            $this->safeQuery('CREATE INDEX idx_audit_logs_target_user_id ON audit_logs (target_user_id)');
        }
    }

    public function down()
    {
        if (! $this->tableExists('audit_logs')) {
            return;
        }

        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_target_user_id');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_actor_user_id');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_event');

        foreach (['meta', 'user_agent', 'ip', 'status', 'target_user_id', 'actor_user_id', 'event'] as $field) {
            if ($this->fieldExists('audit_logs', $field)) {
                $this->safeDropColumn('audit_logs', $field);
            }
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        $this->safeQuery("DROP INDEX {$indexName} ON {$table}");
    }

    private function safeDropColumn(string $table, string $field): void
    {
        try {
            $this->forge->dropColumn($table, $field);
        } catch (Throwable) {
            // Keep rollback idempotent in partial schemas.
        }
    }

    private function safeQuery(string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (Throwable) {
            // Keep migration idempotent across repeated runs.
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

    private function indexExists(string $table, string $indexName): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA index_list('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $result = $this->db
            ->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName])
            ->getRowArray();

        return is_array($result);
    }
}
