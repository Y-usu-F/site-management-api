<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRbacRelationLifecycleFields extends Migration
{
    public function up()
    {
        if ($this->tableExists('role_permissions')) {
            if (! $this->fieldExists('role_permissions', 'is_active')) {
                $this->forge->addColumn('role_permissions', [
                    'is_active' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'null' => false,
                        'default' => 1,
                        'after' => 'permission_id',
                    ],
                ]);
            }
        }

        if ($this->tableExists('user_roles')) {
            if (! $this->fieldExists('user_roles', 'is_active')) {
                $this->forge->addColumn('user_roles', [
                    'is_active' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'null' => false,
                        'default' => 1,
                        'after' => 'role_id',
                    ],
                ]);
            }

            if (! $this->fieldExists('user_roles', 'role_version')) {
                $this->forge->addColumn('user_roles', [
                    'role_version' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => false,
                        'default' => 1,
                        'after' => 'is_active',
                    ],
                ]);
            }

            $this->addUserRolesIndexes();
        }
    }

    public function down()
    {
        if ($this->tableExists('user_roles')) {
            $this->dropUserRolesIndexes();

            if ($this->fieldExists('user_roles', 'role_version')) {
                $this->forge->dropColumn('user_roles', 'role_version');
            }
            if ($this->fieldExists('user_roles', 'is_active')) {
                $this->forge->dropColumn('user_roles', 'is_active');
            }
        }

        if ($this->tableExists('role_permissions')) {
            if ($this->fieldExists('role_permissions', 'is_active')) {
                $this->forge->dropColumn('role_permissions', 'is_active');
            }
        }
    }

    private function addUserRolesIndexes(): void
    {
        // role_version invalidation akislarinda sorgu/scan maliyetini dusurur.
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('CREATE INDEX IF NOT EXISTS idx_user_roles_is_active ON user_roles (is_active)');
            $this->db->query('CREATE INDEX IF NOT EXISTS idx_user_roles_role_version ON user_roles (role_version)');

            return;
        }

        if (! $this->mysqlIndexExists('user_roles', 'idx_user_roles_is_active')) {
            $this->db->query('CREATE INDEX idx_user_roles_is_active ON user_roles (is_active)');
        }
        if (! $this->mysqlIndexExists('user_roles', 'idx_user_roles_role_version')) {
            $this->db->query('CREATE INDEX idx_user_roles_role_version ON user_roles (role_version)');
        }
    }

    private function dropUserRolesIndexes(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query('DROP INDEX IF EXISTS idx_user_roles_is_active');
            $this->db->query('DROP INDEX IF EXISTS idx_user_roles_role_version');

            return;
        }

        if ($this->mysqlIndexExists('user_roles', 'idx_user_roles_is_active')) {
            $this->db->query('DROP INDEX idx_user_roles_is_active ON user_roles');
        }
        if ($this->mysqlIndexExists('user_roles', 'idx_user_roles_role_version')) {
            $this->db->query('DROP INDEX idx_user_roles_role_version ON user_roles');
        }
    }

    private function mysqlIndexExists(string $table, string $index): bool
    {
        $row = $this->db->query(
            "SHOW INDEX FROM {$table} WHERE Key_name = ?",
            [$index]
        )->getRowArray();

        return is_array($row);
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

