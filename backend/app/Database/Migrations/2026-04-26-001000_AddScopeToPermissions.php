<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddScopeToPermissions extends Migration
{
    private const SCOPE_COLUMN = 'scope';
    private const MYSQL_CHECK_NAME = 'chk_permissions_scope';
    private const SQLITE_INSERT_TRIGGER = 'permissions_scope_insert_check';
    private const SQLITE_UPDATE_TRIGGER = 'permissions_scope_update_check';

    public function up()
    {
        if (! $this->tableExists('permissions')) {
            return;
        }

        if (! $this->fieldExists('permissions', self::SCOPE_COLUMN)) {
            $this->forge->addColumn('permissions', [
                self::SCOPE_COLUMN => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'company',
                    'after' => 'code',
                ],
            ]);
        }

        // Legacy rows are scoped to company by default until catalog/seed alignment in RBAC-503.
        if ($this->fieldExists('permissions', self::SCOPE_COLUMN)) {
            $this->db->table('permissions')
                ->where(self::SCOPE_COLUMN, null)
                ->update([self::SCOPE_COLUMN => 'company']);
        }

        $this->applyScopeConstraint();
    }

    public function down()
    {
        if (! $this->tableExists('permissions')) {
            return;
        }

        $this->dropScopeConstraint();

        if ($this->fieldExists('permissions', self::SCOPE_COLUMN)) {
            try {
                $this->forge->dropColumn('permissions', self::SCOPE_COLUMN);
            } catch (Throwable) {
                // Keep rollback idempotent when column is already absent.
            }
        }
    }

    private function applyScopeConstraint(): void
    {
        $driver = $this->db->DBDriver;

        if ($driver === 'MySQLi') {
            try {
                $this->db->query(
                    "ALTER TABLE permissions ADD CONSTRAINT " . self::MYSQL_CHECK_NAME
                    . " CHECK (scope IN ('system','company'))"
                );
            } catch (Throwable) {
                // MySQL/Maria versions may not enforce CHECK. RBAC-503 will add catalog-level guard.
            }

            return;
        }

        if ($driver === 'SQLite3') {
            $this->db->query(
                'CREATE TRIGGER IF NOT EXISTS ' . self::SQLITE_INSERT_TRIGGER . "
                BEFORE INSERT ON permissions
                FOR EACH ROW
                WHEN NEW.scope NOT IN ('system','company')
                BEGIN
                    SELECT RAISE(ABORT, 'invalid permissions.scope');
                END;"
            );

            $this->db->query(
                'CREATE TRIGGER IF NOT EXISTS ' . self::SQLITE_UPDATE_TRIGGER . "
                BEFORE UPDATE OF scope ON permissions
                FOR EACH ROW
                WHEN NEW.scope NOT IN ('system','company')
                BEGIN
                    SELECT RAISE(ABORT, 'invalid permissions.scope');
                END;"
            );
        }
    }

    private function dropScopeConstraint(): void
    {
        $driver = $this->db->DBDriver;

        if ($driver === 'MySQLi') {
            try {
                $this->db->query('ALTER TABLE permissions DROP CHECK ' . self::MYSQL_CHECK_NAME);
            } catch (Throwable) {
                // Ignore to keep rollback resilient across engine versions.
            }

            return;
        }

        if ($driver === 'SQLite3') {
            $this->db->query('DROP TRIGGER IF EXISTS ' . self::SQLITE_INSERT_TRIGGER);
            $this->db->query('DROP TRIGGER IF EXISTS ' . self::SQLITE_UPDATE_TRIGGER);
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

