<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVisitorSecurityTables extends Migration
{
    public function up()
    {
        $this->createVisitorInvites();
        $this->createVisitorEntries();
        $this->createSecurityIncidents();
        $this->createVehicleAccessLists();
    }

    public function down()
    {
        $this->forge->dropTable('vehicle_access_lists', true);
        $this->forge->dropTable('security_incidents', true);
        $this->forge->dropTable('visitor_entries', true);
        $this->forge->dropTable('visitor_invites', true);
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

    private function createVisitorInvites(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'invite_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'visitor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'visitor_phone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'vehicle_plate' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'valid_from' => ['type' => 'DATETIME', 'null' => false],
            'valid_until' => ['type' => 'DATETIME', 'null' => false],
            'max_uses' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'used_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'invite_code'], 'uq_visitor_invites_company_code');
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'status', 'valid_until'], false, false, 'idx_visitor_invites_company_scope_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_vi_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_vi_site');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'CASCADE', 'RESTRICT', 'fk_vi_unit');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_vi_resident');
        $this->forge->createTable('visitor_invites', true);
    }

    private function createVisitorEntries(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'visitor_invite_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'visitor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'visitor_phone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'vehicle_plate' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'entry_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'entered_at' => ['type' => 'DATETIME', 'null' => true],
            'exited_at' => ['type' => 'DATETIME', 'null' => true],
            'recorded_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'direction'], false, false, 'idx_visitor_entries_company_scope_direction');
        $this->forge->addKey(['company_id', 'visitor_invite_id'], false, false, 'idx_visitor_entries_company_invite');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_ve_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_ve_site');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_ve_unit');
        $this->forge->addForeignKey('visitor_invite_id', 'visitor_invites', 'id', 'SET NULL', 'RESTRICT', 'fk_ve_invite');
        $this->forge->createTable('visitor_entries', true);
    }

    private function createSecurityIncidents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'severity' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'reported_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'status', 'severity'], false, false, 'idx_security_incidents_company_site_status_severity');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_si_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_si_site');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_si_unit');
        $this->forge->createTable('security_incidents', true);
    }

    private function createVehicleAccessLists(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'plate_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'list_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'reason' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'plate_number', 'list_type', 'status'], false, false, 'idx_val_company_site_plate_list_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_val_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_val_site');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_val_unit');
        $this->forge->createTable('vehicle_access_lists', true);
    }
}
