<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StandardizeAuditLogSchema extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('ip_address', 'audit_logs')) {
            $fields['ip_address'] = [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'after' => 'ip',
            ];
        }

        if (! $this->db->fieldExists('request_id', 'audit_logs')) {
            $fields['request_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'user_agent',
            ];
        }

        if (! $this->db->fieldExists('occurred_at', 'audit_logs')) {
            $fields['occurred_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'request_id',
            ];
        }

        if (! $this->db->fieldExists('old_values', 'audit_logs')) {
            $fields['old_values'] = [
                'type' => 'JSON',
                'null' => true,
                'after' => 'old_data',
            ];
        }

        if (! $this->db->fieldExists('new_values', 'audit_logs')) {
            $fields['new_values'] = [
                'type' => 'JSON',
                'null' => true,
                'after' => 'new_data',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('audit_logs', $fields);
        }

        $this->createIndexIfMissing('audit_logs', 'idx_audit_logs_request_id', 'request_id');
        $this->createIndexIfMissing('audit_logs', 'idx_audit_logs_occurred_at', 'occurred_at');
        $this->createIndexIfMissing('audit_logs', 'idx_audit_logs_action', 'action');
    }

    public function down()
    {
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_action');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_occurred_at');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_request_id');

        foreach (['new_values', 'old_values', 'occurred_at', 'request_id', 'ip_address'] as $column) {
            if ($this->db->fieldExists($column, 'audit_logs')) {
                $this->forge->dropColumn('audit_logs', $column);
            }
        }
    }

    private function createIndexIfMissing(string $table, string $indexName, string $column): void
    {
        $result = $this->db
            ->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")
            ->getResultArray();

        if ($result === []) {
            $this->db->query("CREATE INDEX {$indexName} ON {$table} ({$column})");
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $result = $this->db
            ->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")
            ->getResultArray();

        if ($result !== []) {
            $this->db->query("DROP INDEX {$indexName} ON {$table}");
        }
    }
}
