<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentManagementTables extends Migration
{
    public function up()
    {
        $this->createDocumentCategories();
        $this->createDocuments();
        $this->createDocumentVersions();
        $this->createDocumentAccessRules();
    }

    public function down()
    {
        $this->forge->dropTable('document_access_rules', true);
        $this->forge->dropTable('document_versions', true);
        $this->forge->dropTable('documents', true);
        $this->forge->dropTable('document_categories', true);
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

    private function createDocumentCategories(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'code'], 'uq_document_categories_company_code');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_document_categories_company');
        $this->forge->createTable('document_categories', true);
    }

    private function createDocuments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'staff_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 220, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'document_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'private'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'resident_profile_id', 'staff_profile_id', 'status'], false, false, 'idx_documents_company_scope_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_documents_company');
        $this->forge->addForeignKey('category_id', 'document_categories', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_category');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_site');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_block');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_unit');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_resident');
        $this->forge->addForeignKey('staff_profile_id', 'staff_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_staff');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_documents_created_by');
        $this->forge->createTable('documents', true);
    }

    private function createDocumentVersions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'document_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'version_no' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'file_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'checksum' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'uploaded_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'document_id', 'version_no'], 'uq_document_versions_company_document_version');
        $this->forge->addKey(['company_id', 'document_id', 'checksum'], false, false, 'idx_document_versions_company_document_checksum');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_document_versions_company');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'RESTRICT', 'fk_document_versions_document');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_document_versions_uploaded_by');
        $this->forge->createTable('document_versions', true);
    }

    private function createDocumentAccessRules(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'document_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'rule_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'rule_value' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'permission' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'document_id', 'rule_type', 'rule_value', 'permission'], 'uq_document_access_rules_dedup');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_document_access_rules_company');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'RESTRICT', 'fk_document_access_rules_document');
        $this->forge->createTable('document_access_rules', true);
    }
}
