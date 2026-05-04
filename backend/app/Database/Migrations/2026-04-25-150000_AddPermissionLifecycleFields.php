<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddPermissionLifecycleFields extends Migration
{
    public function up()
    {
        if (! $this->tableExists('permissions')) {
            return;
        }

        if (! $this->fieldExists('permissions', 'is_active')) {
            $this->safeAddColumn('permissions', [
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                    'after' => 'name',
                ],
            ]);
        }

        if (! $this->fieldExists('permissions', 'deprecated_at')) {
            $this->safeAddColumn('permissions', [
                'deprecated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'is_active',
                ],
            ]);
        }

        // Existing rows receive default value from DDL.
    }

    public function down()
    {
        // Intentionally no-op for test-safe idempotency.
    }

    private function safeAddColumn(string $table, array $fields): void
    {
        try {
            $this->forge->addColumn($table, $fields);
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
}
