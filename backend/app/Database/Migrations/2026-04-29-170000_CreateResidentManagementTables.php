<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResidentManagementTables extends Migration
{
    public function up()
    {
        $this->createResidentProfilesTable();
        $this->createUnitOccupanciesTable();
        $this->createResidentContactsTable();
        $this->createResidentVehiclesTable();
    }

    public function down()
    {
        $this->forge->dropTable('resident_vehicles', true);
        $this->forge->dropTable('resident_contacts', true);
        $this->forge->dropTable('unit_occupancies', true);
        $this->forge->dropTable('resident_profiles', true);
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

    private function createResidentProfilesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'identity_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'birth_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey(['company_id', 'identity_number'], 'uq_resident_profiles_company_identity');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_resident_profiles_company_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_resident_profiles_user_id');
        $this->forge->createTable('resident_profiles', true);
    }

    private function createUnitOccupanciesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'relationship_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'start_date' => ['type' => 'DATE', 'null' => false],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'unit_id']);
        $this->forge->addKey(['company_id', 'resident_profile_id']);
        $this->forge->addKey(['company_id', 'relationship_type', 'status'], false, false, 'idx_unit_occupancies_type_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_unit_occupancies_company_id');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'CASCADE', 'RESTRICT', 'fk_unit_occupancies_unit_id');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'CASCADE', 'RESTRICT', 'fk_unit_occupancies_resident_id');
        $this->forge->createTable('unit_occupancies', true);
    }

    private function createResidentContactsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'value' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'resident_profile_id']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_resident_contacts_company_id');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'CASCADE', 'RESTRICT', 'fk_resident_contacts_resident_id');
        $this->forge->createTable('resident_contacts', true);
    }

    private function createResidentVehiclesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'plate_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'brand' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'model' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'color' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'parking_slot' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'resident_profile_id']);
        $this->forge->addKey(['company_id', 'plate_number', 'status'], false, false, 'idx_resident_vehicles_plate_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_resident_vehicles_company_id');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'CASCADE', 'RESTRICT', 'fk_resident_vehicles_resident_id');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_resident_vehicles_unit_id');
        $this->forge->createTable('resident_vehicles', true);
    }
}
