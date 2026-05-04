<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOperationManagementTables extends Migration
{
    public function up()
    {
        $this->createRequestCategories();
        $this->createServiceRequests();
        $this->createServiceRequestComments();
        $this->createServiceRequestFiles();
        $this->createWorkOrders();
    }

    public function down()
    {
        $this->forge->dropTable('work_orders', true);
        $this->forge->dropTable('service_request_files', true);
        $this->forge->dropTable('service_request_comments', true);
        $this->forge->dropTable('service_requests', true);
        $this->forge->dropTable('request_categories', true);
    }

    private function addAuditStampFields(): void
    {
        $this->forge->addField([
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    private function createRequestCategories(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'active'],
        ]);
        $this->addAuditStampFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'status'], false, false, 'idx_request_categories_company_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_request_categories_company');
        $this->forge->createTable('request_categories', true);
    }

    private function createServiceRequests(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'block_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'request_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => false],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'panel'],
            'assigned_to_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'first_response_at' => ['type' => 'DATETIME', 'null' => true],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->addAuditStampFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'request_no'], 'uq_service_requests_company_request_no');
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'status'], false, false, 'idx_service_requests_company_site_unit_status');
        $this->forge->addKey(['company_id', 'assigned_to_user_id', 'status'], false, false, 'idx_service_requests_company_assignee_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_service_requests_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_service_requests_site');
        $this->forge->addForeignKey('block_id', 'blocks', 'id', 'SET NULL', 'RESTRICT', 'fk_service_requests_block');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_service_requests_unit');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_service_requests_resident');
        $this->forge->addForeignKey('category_id', 'request_categories', 'id', 'SET NULL', 'RESTRICT', 'fk_service_requests_category');
        $this->forge->addForeignKey('assigned_to_user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_service_requests_assignee');
        $this->forge->createTable('service_requests', true);
    }

    private function createServiceRequestComments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'service_request_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'comment' => ['type' => 'TEXT', 'null' => false],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'public'],
        ]);
        $this->addAuditStampFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'service_request_id'], false, false, 'idx_sr_comments_company_sr');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_sr_comments_company');
        $this->forge->addForeignKey('service_request_id', 'service_requests', 'id', 'CASCADE', 'RESTRICT', 'fk_sr_comments_request');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_sr_comments_user');
        $this->forge->createTable('service_request_comments', true);
    }

    private function createServiceRequestFiles(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'service_request_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'file_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'uploaded_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->addAuditStampFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'service_request_id'], false, false, 'idx_sr_files_company_sr');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_sr_files_company');
        $this->forge->addForeignKey('service_request_id', 'service_requests', 'id', 'CASCADE', 'RESTRICT', 'fk_sr_files_request');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_sr_files_uploader');
        $this->forge->createTable('service_request_files', true);
    }

    private function createWorkOrders(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'service_request_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'assigned_to_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vendor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'planned_start_at' => ['type' => 'DATETIME', 'null' => true],
            'planned_end_at' => ['type' => 'DATETIME', 'null' => true],
            'started_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'cost_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditStampFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'service_request_id', 'status'], false, false, 'idx_work_orders_company_sr_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_work_orders_company');
        $this->forge->addForeignKey('service_request_id', 'service_requests', 'id', 'CASCADE', 'RESTRICT', 'fk_work_orders_service_request');
        $this->forge->addForeignKey('assigned_to_user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_work_orders_assignee');
        $this->forge->createTable('work_orders', true);
    }
}
