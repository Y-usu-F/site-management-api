<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssetMaintenanceTables extends Migration
{
    public function up()
    {
        $this->createAssets();
        $this->createPlans();
        $this->createRecords();
    }

    public function down()
    {
        $this->forge->dropTable('asset_maintenance_records', true);
        $this->forge->dropTable('asset_maintenance_plans', true);
        $this->forge->dropTable('assets', true);
    }

    private function addAuditFields(): void
    {
        $this->forge->addField([
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    private function createAssets(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'asset_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'asset_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => false],
            'brand' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'model' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'purchase_date' => ['type' => 'DATE', 'null' => true],
            'warranty_until' => ['type' => 'DATE', 'null' => true],
            'location_note' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'asset_no'], 'uq_assets_company_asset_no');
        $this->forge->addKey(['company_id', 'site_id', 'block_id', 'unit_id', 'status'], false, false, 'idx_assets_company_scope_status');
        $this->forge->addKey(['company_id', 'serial_number', 'status'], false, false, 'idx_assets_company_serial_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_assets_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_assets_site');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'SET NULL', 'RESTRICT', 'fk_assets_block');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_assets_unit');
        $this->forge->createTable('assets', true);
    }

    private function createPlans(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'asset_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'frequency_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'frequency_interval' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'next_due_date' => ['type' => 'DATE', 'null' => false],
            'vendor_name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'asset_id', 'status', 'next_due_date'], false, false, 'idx_asset_plans_company_asset_status_due');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_asset_plans_company');
        $this->forge->addForeignKey('asset_id', 'assets', 'id', 'CASCADE', 'RESTRICT', 'fk_asset_plans_asset');
        $this->forge->createTable('asset_maintenance_plans', true);
    }

    private function createRecords(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'asset_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'maintenance_plan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'performed_at' => ['type' => 'DATETIME', 'null' => false],
            'performed_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'vendor_name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'cost_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'next_due_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'completed'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'asset_id', 'performed_at'], false, false, 'idx_asset_records_company_asset_performed');
        $this->forge->addKey(['company_id', 'maintenance_plan_id'], false, false, 'idx_asset_records_company_plan');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_asset_records_company');
        $this->forge->addForeignKey('asset_id', 'assets', 'id', 'CASCADE', 'RESTRICT', 'fk_asset_records_asset');
        $this->forge->addForeignKey('maintenance_plan_id', 'asset_maintenance_plans', 'id', 'SET NULL', 'RESTRICT', 'fk_asset_records_plan');
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'SET NULL', 'RESTRICT', 'fk_asset_records_work_order');
        $this->forge->createTable('asset_maintenance_records', true);
    }
}
