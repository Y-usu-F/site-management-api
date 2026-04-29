<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersForAuthSecurityFields extends Migration
{
    public function up()
    {
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

        $this->forge->addColumn('users', $fields);
        $this->db->query('CREATE INDEX idx_users_is_active ON users (is_active)');
    }

    public function down()
    {
        $index = $this->db->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_is_active'")->getResultArray();
        if ($index !== []) {
            $this->db->query('DROP INDEX idx_users_is_active ON users');
        }
        $this->forge->dropColumn('users', 'locked_until');
        $this->forge->dropColumn('users', 'failed_login_count');
        $this->forge->dropColumn('users', 'last_login_at');
        $this->forge->dropColumn('users', 'password_changed_at');
        $this->forge->dropColumn('users', 'is_active');
    }
}
