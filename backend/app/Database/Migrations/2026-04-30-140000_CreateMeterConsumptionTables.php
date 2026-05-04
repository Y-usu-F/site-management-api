<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMeterConsumptionTables extends Migration
{
    public function up()
    {
        $this->createMeters();
        $this->createReadingPeriods();
        $this->createReadings();
        $this->createConsumptionReports();
    }

    public function down()
    {
        $this->forge->dropTable('consumption_reports', true);
        $this->forge->dropTable('meter_readings', true);
        $this->forge->dropTable('meter_reading_periods', true);
        $this->forge->dropTable('meters', true);
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

    private function createMeters(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'meter_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'meter_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'scope' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'meter_no'], 'uq_meters_company_meter_no');
        $this->forge->addKey(['company_id', 'site_id', 'block_id', 'unit_id', 'scope'], false, false, 'idx_meters_company_scope');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_meters_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_meters_site');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'SET NULL', 'RESTRICT', 'fk_meters_block');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_meters_unit');
        $this->forge->createTable('meters', true);
    }

    private function createReadingPeriods(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'period_key' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => false],
            'start_date' => ['type' => 'DATE', 'null' => false],
            'end_date' => ['type' => 'DATE', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'site_id', 'period_key'], 'uq_meter_period_company_site_key');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_meter_period_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_meter_period_site');
        $this->forge->createTable('meter_reading_periods', true);
    }

    private function createReadings(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'meter_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'reading_period_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'previous_index' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => false],
            'current_index' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => false],
            'consumption' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => false],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '12,4', 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'reading_date' => ['type' => 'DATE', 'null' => false],
            'source' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'submitted_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'rejected_reason' => ['type' => 'TEXT', 'null' => true],
            'photo_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'meter_id', 'reading_period_id', 'status'], 'uq_meter_readings_company_meter_period_status');
        $this->forge->addKey(['company_id', 'meter_id', 'reading_period_id'], false, false, 'idx_meter_readings_company_meter_period');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_meter_readings_company');
        $this->forge->addForeignKey('meter_id', 'meters', 'id', 'CASCADE', 'RESTRICT', 'fk_meter_readings_meter');
        $this->forge->addForeignKey('reading_period_id', 'meter_reading_periods', 'id', 'CASCADE', 'RESTRICT', 'fk_meter_readings_period');
        $this->forge->addForeignKey('submitted_by_user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_meter_readings_submitted_by');
        $this->forge->addForeignKey('approved_by_user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_meter_readings_approved_by');
        $this->forge->createTable('meter_readings', true);
    }

    private function createConsumptionReports(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'reading_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'due_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'reading_id'], 'uq_consumption_reports_company_reading');
        $this->forge->addKey(['company_id', 'unit_id', 'status'], false, false, 'idx_consumption_reports_company_unit_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_consumption_reports_company');
        $this->forge->addForeignKey('reading_id', 'meter_readings', 'id', 'CASCADE', 'RESTRICT', 'fk_consumption_reports_reading');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_consumption_reports_unit');
        $this->forge->addForeignKey('due_item_id', 'due_items', 'id', 'SET NULL', 'RESTRICT', 'fk_consumption_reports_due_item');
        $this->forge->createTable('consumption_reports', true);
    }
}
