<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteManagementTables extends Migration
{
    public function up()
    {
        $this->createSitesTable();
        $this->createBlocksTable();
        $this->createFloorsTable();
        $this->createUnitsTable();
    }

    public function down()
    {
        $this->forge->dropTable('units', true);
        $this->forge->dropTable('floors', true);
        $this->forge->dropTable('blocks', true);
        $this->forge->dropTable('sites', true);
    }

    private function addStandardFields(): void
    {
        $this->forge->addField([
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    private function createSitesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'public_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'address' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addUniqueKey('public_id', 'uq_sites_public_id');
        $this->forge->addUniqueKey(['company_id', 'code'], 'uq_sites_company_code');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_sites_company_id');
        $this->forge->createTable('sites', true);
    }

    private function createBlocksTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id']);
        $this->forge->addUniqueKey(['company_id', 'site_id', 'code'], 'uq_blocks_company_site_code');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_blocks_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_blocks_site_id');
        $this->forge->createTable('blocks', true);
    }

    private function createFloorsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'number' => ['type' => 'INT', 'null' => false],
            'label' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'block_id']);
        $this->forge->addUniqueKey(['company_id', 'block_id', 'number'], 'uq_floors_company_block_number');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_floors_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_floors_site_id');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'CASCADE', 'RESTRICT', 'fk_floors_block_id');
        $this->forge->createTable('floors', true);
    }

    private function createUnitsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'floor_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'gross_area' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'net_area' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'occupant_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'block_id', 'floor_id']);
        $this->forge->addUniqueKey(['company_id', 'floor_id', 'unit_no'], 'uq_units_company_floor_unit_no');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_units_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_units_site_id');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'CASCADE', 'RESTRICT', 'fk_units_block_id');
        $this->forge->addForeignKey('floor_id', 'floors', 'id', 'CASCADE', 'RESTRICT', 'fk_units_floor_id');
        $this->forge->createTable('units', true);
    }
}
