<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLegalCaseTables extends Migration
{
    public function up()
    {
        $this->createLegalCases();
        $this->createLegalCaseDebts();
        $this->createLegalCaseEvents();
        $this->createLegalCaseDocuments();
    }

    public function down()
    {
        $this->forge->dropTable('legal_case_documents', true);
        $this->forge->dropTable('legal_case_events', true);
        $this->forge->dropTable('legal_case_debts', true);
        $this->forge->dropTable('legal_cases', true);
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

    private function createLegalCases(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'case_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'case_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'lawyer_name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'enforcement_office' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'file_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'principal_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'interest_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'expense_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'opened_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'case_no'], 'uq_legal_cases_company_case_no');
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'resident_profile_id', 'status'], false, false, 'idx_legal_cases_company_scope_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_cases_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_cases_site');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_legal_cases_unit');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_legal_cases_resident');
        $this->forge->createTable('legal_cases', true);
    }

    private function createLegalCaseDebts(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'legal_case_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'principal_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'interest_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'legal_case_id', 'due_item_id'], 'uq_legal_case_debts_case_due');
        $this->forge->addKey(['company_id', 'due_item_id', 'status'], false, false, 'idx_legal_case_debts_due_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_debts_company');
        $this->forge->addForeignKey('legal_case_id', 'legal_cases', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_debts_case');
        $this->forge->addForeignKey('due_item_id', 'due_items', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_debts_due_item');
        $this->forge->createTable('legal_case_debts', true);
    }

    private function createLegalCaseEvents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'legal_case_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'event_date' => ['type' => 'DATETIME', 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'legal_case_id', 'event_date'], false, false, 'idx_legal_case_events_case_date');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_events_company');
        $this->forge->addForeignKey('legal_case_id', 'legal_cases', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_events_case');
        $this->forge->createTable('legal_case_events', true);
    }

    private function createLegalCaseDocuments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'legal_case_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'document_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'document_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'legal_case_id', 'document_id'], 'uq_legal_case_documents_case_document');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_documents_company');
        $this->forge->addForeignKey('legal_case_id', 'legal_cases', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_documents_case');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'RESTRICT', 'fk_legal_case_documents_document');
        $this->forge->createTable('legal_case_documents', true);
    }
}
