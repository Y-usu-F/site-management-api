<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterAuditLogsForAuthEvents extends Migration
{
    public function up()
    {
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

        $this->forge->addColumn('audit_logs', $fields);

        $this->db->query('CREATE INDEX idx_audit_logs_event ON audit_logs (event)');
        $this->db->query('CREATE INDEX idx_audit_logs_actor_user_id ON audit_logs (actor_user_id)');
        $this->db->query('CREATE INDEX idx_audit_logs_target_user_id ON audit_logs (target_user_id)');
    }

    public function down()
    {
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_target_user_id');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_actor_user_id');
        $this->dropIndexIfExists('audit_logs', 'idx_audit_logs_event');

        $this->forge->dropColumn('audit_logs', 'meta');
        $this->forge->dropColumn('audit_logs', 'user_agent');
        $this->forge->dropColumn('audit_logs', 'ip');
        $this->forge->dropColumn('audit_logs', 'status');
        $this->forge->dropColumn('audit_logs', 'target_user_id');
        $this->forge->dropColumn('audit_logs', 'actor_user_id');
        $this->forge->dropColumn('audit_logs', 'event');
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
