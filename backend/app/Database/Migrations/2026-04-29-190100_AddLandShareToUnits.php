<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddLandShareToUnits extends Migration
{
    public function up()
    {
        if (! $this->tableExists('units')) {
            return;
        }

        if (! $this->fieldExists('units', 'land_share')) {
            try {
                $this->forge->addColumn('units', [
                'land_share' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,4',
                    'null' => true,
                    'after' => 'net_area',
                ],
                ]);
            } catch (Throwable) {
                // Keep migration idempotent.
            }
        }
    }

    public function down()
    {
        if (! $this->tableExists('units')) {
            return;
        }

        if ($this->fieldExists('units', 'land_share')) {
            try {
                $this->forge->dropColumn('units', 'land_share');
            } catch (Throwable) {
                // Keep rollback idempotent.
            }
        }
    }

    private function tableExists(string $table): bool
    {
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
}
