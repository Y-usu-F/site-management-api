<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDueManagementTables extends Migration
{
    public function up()
    {
        $this->createDueDefinitions();
        $this->createDuePeriods();
        $this->createDueBatches();
        $this->createDueItems();
    }

    public function down()
    {
        $this->forge->dropTable('due_items', true);
        $this->forge->dropTable('due_batches', true);
        $this->forge->dropTable('due_periods', true);
        $this->forge->dropTable('due_definitions', true);
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

    private function createDueDefinitions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'calculation_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'status']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_due_definitions_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'SET NULL', 'RESTRICT', 'fk_due_definitions_site_id');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'SET NULL', 'RESTRICT', 'fk_due_definitions_block_id');
        $this->forge->createTable('due_definitions', true);
    }

    private function createDuePeriods(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'period_key' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => false],
            'start_date' => ['type' => 'DATE', 'null' => false],
            'end_date' => ['type' => 'DATE', 'null' => false],
            'due_date' => ['type' => 'DATE', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'site_id', 'period_key', 'status'], 'uq_due_periods_company_site_period_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_due_periods_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_due_periods_site_id');
        $this->forge->createTable('due_periods', true);
    }

    private function createDueBatches(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_definition_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_period_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'batch_key' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'total_units' => ['type' => 'INT', 'default' => 0],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'processing'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'due_definition_id', 'due_period_id'], 'uq_due_batches_company_definition_period');
        $this->forge->addKey('batch_key');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_due_batches_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_due_batches_site_id');
        $this->forge->addForeignKey('due_definition_id', 'due_definitions', 'id', 'CASCADE', 'RESTRICT', 'fk_due_batches_definition_id');
        $this->forge->addForeignKey('due_period_id', 'due_periods', 'id', 'CASCADE', 'RESTRICT', 'fk_due_batches_period_id');
        $this->forge->createTable('due_batches', true);
    }

    private function createDueItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'floor_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_definition_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_period_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_batch_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'remaining_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'due_date' => ['type' => 'DATE', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unpaid'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'due_period_id', 'status'], false, false, 'idx_due_items_company_site_unit_period_status');
        $this->forge->addKey(['company_id', 'due_date', 'status'], false, false, 'idx_due_items_company_due_date_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_due_items_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_due_items_site_id');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'CASCADE', 'RESTRICT', 'fk_due_items_block_id');
        $this->forge->addForeignKey('floor_id', 'floors', 'id', 'SET NULL', 'RESTRICT', 'fk_due_items_floor_id');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'CASCADE', 'RESTRICT', 'fk_due_items_unit_id');
        $this->forge->addForeignKey('due_definition_id', 'due_definitions', 'id', 'CASCADE', 'RESTRICT', 'fk_due_items_definition_id');
        $this->forge->addForeignKey('due_period_id', 'due_periods', 'id', 'CASCADE', 'RESTRICT', 'fk_due_items_period_id');
        $this->forge->addForeignKey('due_batch_id', 'due_batches', 'id', 'SET NULL', 'RESTRICT', 'fk_due_items_batch_id');
        $this->forge->createTable('due_items', true);
    }
}
