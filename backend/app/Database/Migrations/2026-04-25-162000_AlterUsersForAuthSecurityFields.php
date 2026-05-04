<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AlterUsersForAuthSecurityFields extends Migration
{
    public function up()
    {
        if (! $this->tableExists('users')) {
            return;
        }

        $fields = [
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'after' => 'status',
            ],
            'password_changed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_active',
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'password_changed_at',
            ],
            'failed_login_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
                'default' => 0,
                'after' => 'last_login_at',
            ],
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'failed_login_count',
            ],
        ];

        $additions = [];
        foreach ($fields as $name => $definition) {
            if (! $this->fieldExists('users', $name)) {
                $additions[$name] = $definition;
            }
        }

        if ($additions !== []) {
            $this->forge->addColumn('users', $additions);
        }

        if ($this->fieldExists('users', 'is_active') && ! $this->indexExists('users', 'idx_users_is_active')) {
            $this->safeQuery('CREATE INDEX idx_users_is_active ON users (is_active)');
        }
    }

    public function down()
    {
        if (! $this->tableExists('users')) {
            return;
        }

        if ($this->indexExists('users', 'idx_users_is_active')) {
            $this->safeQuery('DROP INDEX idx_users_is_active ON users');
        }

        foreach (['locked_until', 'failed_login_count', 'last_login_at', 'password_changed_at', 'is_active'] as $field) {
            if ($this->fieldExists('users', $field)) {
                $this->safeDropColumn('users', $field);
            }
        }
    }

    private function safeDropColumn(string $table, string $field): void
    {
        try {
            $this->forge->dropColumn($table, $field);
        } catch (Throwable) {
            // Keep rollback idempotent.
        }
    }

    private function safeQuery(string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (Throwable) {
            // Keep migration idempotent.
        }
    }

    private function tableExists(string $table): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $row = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table])->getRowArray();
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

    private function indexExists(string $table, string $index): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA index_list('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $index) {
                    return true;
                }
            }
            return false;
        }

        $row = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])->getRowArray();
        return is_array($row);
    }
}
