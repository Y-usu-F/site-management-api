<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreTables extends Migration
{
    public function up()
    {
        $this->createCompanies();
        $this->createUsers();
        $this->createRoles();
        $this->createPermissions();
        $this->createRolePermissions();
        $this->createUserRoles();
        $this->createTokenBlacklist();
        $this->createAuditLogs();
    }

    public function down()
    {
        foreach (['audit_logs', 'token_blacklist', 'user_roles', 'role_permissions', 'permissions', 'roles', 'users', 'companies'] as $table) {
            if ($this->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }

    private function addStandardFields(bool $tenantRequired = true): void
    {
        if ($tenantRequired) {
            $this->forge->addField(['company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false]]);
        }
        $this->forge->addField([
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    private function createCompanies(): void
    {
        if ($this->tableExists('companies')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'public_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('public_id', 'uq_companies_public_id');
        $this->forge->createTable('companies', true);
    }

    private function createUsers(): void
    {
        if ($this->tableExists('users')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'public_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => false],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields(true);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addUniqueKey(['company_id', 'email'], 'uq_users_company_id_email');
        $this->forge->addUniqueKey('public_id', 'uq_users_public_id');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_users_company_id');
        $this->forge->createTable('users', true);
    }

    private function createRoles(): void
    {
        if ($this->tableExists('roles')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'code'], 'uq_roles_company_id_code');
        $this->forge->createTable('roles', true);
    }

    private function createPermissions(): void
    {
        if ($this->tableExists('permissions')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_permissions_code');
        $this->forge->createTable('permissions', true);
    }

    private function createRolePermissions(): void
    {
        if ($this->tableExists('role_permissions')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'permission_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'permission_id'], 'uq_role_permissions_role_permission');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE', 'fk_role_permissions_role_id');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE', 'fk_role_permissions_permission_id');
        $this->forge->createTable('role_permissions', true);
    }

    private function createUserRoles(): void
    {
        if ($this->tableExists('user_roles')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'role_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'user_id', 'role_id'], 'uq_user_roles_company_user_role');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_user_roles_user_id');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE', 'fk_user_roles_role_id');
        $this->forge->createTable('user_roles', true);
    }

    private function createTokenBlacklist(): void
    {
        if ($this->tableExists('token_blacklist')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => false],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addUniqueKey('token_hash', 'uq_token_blacklist_token_hash');
        $this->forge->createTable('token_blacklist', true);
    }

    private function createAuditLogs(): void
    {
        if ($this->tableExists('audit_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'old_data' => ['type' => 'JSON', 'null' => true],
            'new_data' => ['type' => 'JSON', 'null' => true],
        ]);
        $this->addStandardFields(false);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_logs', true);
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
}
