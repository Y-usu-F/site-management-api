<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaffManagementTables extends Migration
{
    public function up()
    {
        $this->createStaffProfiles();
        $this->createStaffAssignments();
        $this->createStaffShifts();
        $this->createStaffTasks();
    }

    public function down()
    {
        $this->forge->dropTable('staff_tasks', true);
        $this->forge->dropTable('staff_shifts', true);
        $this->forge->dropTable('staff_assignments', true);
        $this->forge->dropTable('staff_profiles', true);
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

    private function createStaffProfiles(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'staff_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'user_id', 'status'], false, false, 'idx_staff_profiles_company_user_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_profiles_company');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_staff_profiles_user');
        $this->forge->createTable('staff_profiles', true);
    }

    private function createStaffAssignments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'staff_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'role_note' => ['type' => 'TEXT', 'null' => true],
            'start_date' => ['type' => 'DATE', 'null' => false],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'staff_profile_id', 'site_id', 'block_id', 'status'], false, false, 'idx_staff_assignments_company_staff_scope_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_assignments_company');
        $this->forge->addForeignKey('staff_profile_id', 'staff_profiles', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_assignments_staff');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_assignments_site');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'SET NULL', 'RESTRICT', 'fk_staff_assignments_block');
        $this->forge->createTable('staff_assignments', true);
    }

    private function createStaffShifts(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'staff_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'shift_date' => ['type' => 'DATE', 'null' => false],
            'start_at' => ['type' => 'DATETIME', 'null' => false],
            'end_at' => ['type' => 'DATETIME', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'planned'],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'staff_profile_id', 'shift_date', 'status'], false, false, 'idx_staff_shifts_company_staff_date_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_shifts_company');
        $this->forge->addForeignKey('staff_profile_id', 'staff_profiles', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_shifts_staff');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_shifts_site');
        $this->forge->createTable('staff_shifts', true);
    }

    private function createStaffTasks(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'staff_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'due_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'proof_note' => ['type' => 'TEXT', 'null' => true],
            'proof_file_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'staff_profile_id', 'site_id', 'status'], false, false, 'idx_staff_tasks_company_staff_site_status');
        $this->forge->addKey(['company_id', 'work_order_id'], false, false, 'idx_staff_tasks_company_work_order');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_tasks_company');
        $this->forge->addForeignKey('staff_profile_id', 'staff_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_staff_tasks_staff');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_staff_tasks_site');
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'SET NULL', 'RESTRICT', 'fk_staff_tasks_work_order');
        $this->forge->createTable('staff_tasks', true);
    }
}
