<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSessionFieldsToUserRefreshTokens extends Migration
{
    public function up()
    {
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

        $this->forge->addColumn('user_refresh_tokens', $fields);
        $this->db->query('CREATE INDEX idx_urt_last_used_at ON user_refresh_tokens (last_used_at)');
        $this->db->query('CREATE INDEX idx_urt_token_jti ON user_refresh_tokens (token_jti)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_urt_last_used_at ON user_refresh_tokens');
        $this->db->query('DROP INDEX idx_urt_token_jti ON user_refresh_tokens');
        $this->forge->dropColumn('user_refresh_tokens', 'last_used_at');
        $this->forge->dropColumn('user_refresh_tokens', 'revoked_reason');
        $this->forge->dropColumn('user_refresh_tokens', 'revoked_by');
        $this->forge->dropColumn('user_refresh_tokens', 'device_name');
        $this->forge->dropColumn('user_refresh_tokens', 'token_jti');
    }
}
