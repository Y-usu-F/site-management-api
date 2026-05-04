<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserRefreshTokensTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'family_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => false],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'issued_at' => ['type' => 'DATETIME', 'null' => false],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'replaced_by_token_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'user_id'], false, false, 'idx_urt_company_user');
        $this->forge->addKey('family_id', false, false, 'idx_urt_family_id');
        $this->forge->addKey('revoked_at', false, false, 'idx_urt_revoked_at');
        $this->forge->addKey('expires_at', false, false, 'idx_urt_expires_at');
        $this->forge->addUniqueKey('token_hash', 'uq_urt_token_hash');

        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_urt_user_id');
        $this->forge->createTable('user_refresh_tokens', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_refresh_tokens', true);
    }
}
