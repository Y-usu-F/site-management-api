<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommonAreaReservationTables extends Migration
{
    public function up()
    {
        $this->createCommonAreas();
        $this->createReservations();
    }

    public function down()
    {
        $this->forge->dropTable('common_area_reservations', true);
        $this->forge->dropTable('common_areas', true);
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

    private function createCommonAreas(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'requires_approval' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_paid' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fee_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'site_id', 'code'], 'uq_common_areas_company_site_code');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_common_areas_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_common_areas_site');
        $this->forge->createTable('common_areas', true);
    }

    private function createReservations(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'common_area_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reservation_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'start_at' => ['type' => 'DATETIME', 'null' => false],
            'end_at' => ['type' => 'DATETIME', 'null' => false],
            'participant_count' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'approved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'rejected_reason' => ['type' => 'TEXT', 'null' => true],
            'cancelled_reason' => ['type' => 'TEXT', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'reservation_no'], 'uq_common_area_reservations_company_no');
        $this->forge->addKey(['company_id', 'common_area_id', 'start_at', 'end_at', 'status'], false, false, 'idx_car_company_area_time_status');
        $this->forge->addKey(['company_id', 'unit_id', 'status'], false, false, 'idx_car_company_unit_status');
        $this->forge->addKey(['company_id', 'resident_profile_id', 'status'], false, false, 'idx_car_company_resident_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_car_company');
        $this->forge->addForeignKey('common_area_id', 'common_areas', 'id', 'CASCADE', 'RESTRICT', 'fk_car_common_area');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_car_unit');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_car_resident');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_car_approved_by');
        $this->forge->createTable('common_area_reservations', true);
    }
}
