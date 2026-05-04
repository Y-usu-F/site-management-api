<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddSessionFieldsToUserRefreshTokens extends Migration
{
    public function up()
    {
        if (! $this->tableExists('user_refresh_tokens')) {
            return;
        }

        $fields = [
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'issued_at',
            ],
            'revoked_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'revoked_at',
            ],
            'revoked_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'revoked_reason',
            ],
            'device_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'created_user_agent',
            ],
            'token_jti' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'token_hash',
            ],
        ];

        $additions = [];
        foreach ($fields as $name => $definition) {
            if (! $this->fieldExists('user_refresh_tokens', $name)) {
                $additions[$name] = $definition;
            }
        }
        if ($additions !== []) {
            $this->forge->addColumn('user_refresh_tokens', $additions);
        }

        if ($this->fieldExists('user_refresh_tokens', 'last_used_at') && ! $this->indexExists('user_refresh_tokens', 'idx_urt_last_used_at')) {
            $this->safeQuery('CREATE INDEX idx_urt_last_used_at ON user_refresh_tokens (last_used_at)');
        }
        if ($this->fieldExists('user_refresh_tokens', 'token_jti') && ! $this->indexExists('user_refresh_tokens', 'idx_urt_token_jti')) {
            $this->safeQuery('CREATE INDEX idx_urt_token_jti ON user_refresh_tokens (token_jti)');
        }
    }

    public function down()
    {
        if (! $this->tableExists('user_refresh_tokens')) {
            return;
        }

        if ($this->indexExists('user_refresh_tokens', 'idx_urt_last_used_at')) {
            $this->safeQuery('DROP INDEX idx_urt_last_used_at ON user_refresh_tokens');
        }
        if ($this->indexExists('user_refresh_tokens', 'idx_urt_token_jti')) {
            $this->safeQuery('DROP INDEX idx_urt_token_jti ON user_refresh_tokens');
        }

        foreach (['last_used_at', 'revoked_reason', 'revoked_by', 'device_name', 'token_jti'] as $field) {
            if ($this->fieldExists('user_refresh_tokens', $field)) {
                $this->safeDropColumn('user_refresh_tokens', $field);
            }
        }
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
        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA table_info('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $field) {
                    return true;
                }
            }

            return false;
        }

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

        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $row = $this->db->table('information_schema.statistics')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $index)
            ->get(1)
            ->getRowArray();
        return is_array($row);
    }
}
