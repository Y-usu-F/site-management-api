<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateBlocksUniqueIndexForSoftDelete extends Migration
{
    public function up()
    {
        $this->dropIndexIfExists('uq_blocks_company_site_code');
        $this->db->query('CREATE UNIQUE INDEX uq_blocks_company_site_code_active ON blocks (company_id, site_id, code, deleted_at)');
    }

    public function down()
    {
        $this->dropIndexIfExists('uq_blocks_company_site_code_active');
        $this->db->query('CREATE UNIQUE INDEX uq_blocks_company_site_code ON blocks (company_id, site_id, code)');
    }

    private function dropIndexIfExists(string $indexName): void
    {
        $driver = $this->db->DBDriver;
        if ($driver === 'MySQLi') {
            try {
                $this->db->query('ALTER TABLE blocks DROP INDEX ' . $indexName);
            } catch (\Throwable) {
                // Ignore when index does not exist.
            }
            return;
        }

        try {
            $this->db->query('DROP INDEX IF EXISTS ' . $indexName);
        } catch (\Throwable) {
            // Ignore when index does not exist.
        }
    }
}
