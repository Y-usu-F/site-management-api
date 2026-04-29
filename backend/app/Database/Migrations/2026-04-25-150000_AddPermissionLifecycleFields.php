<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermissionLifecycleFields extends Migration
{
    public function up()
    {
        $fields = [
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'after' => 'name',
            ],
            'deprecated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_active',
            ],
        ];

        $this->forge->addColumn('permissions', $fields);
        $this->db->table('permissions')->where('is_active', null)->update(['is_active' => 1]);
    }

    public function down()
    {
        $this->forge->dropColumn('permissions', 'deprecated_at');
        $this->forge->dropColumn('permissions', 'is_active');
    }
}
