<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentManagementTables extends Migration
{
    public function up()
    {
        $this->createPaymentsTable();
        $this->createPaymentAllocationsTable();
        $this->createPaymentEventsTable();
    }

    public function down()
    {
        $this->forge->dropTable('payment_events', true);
        $this->forge->dropTable('payment_allocations', true);
        $this->forge->dropTable('payments', true);
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

    private function createPaymentsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'payment_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'provider_reference' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'allocated_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'payment_date' => ['type' => 'DATETIME', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'method' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'payment_no'], 'uq_payments_company_payment_no');
        $this->forge->addUniqueKey(['company_id', 'idempotency_key'], 'uq_payments_company_idempotency');
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'status'], false, false, 'idx_payments_company_site_unit_status');
        $this->forge->addKey(['company_id', 'payment_date', 'status'], false, false, 'idx_payments_company_payment_date_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_payments_company_id');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_payments_site_id');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_payments_unit_id');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_payments_resident_id');
        $this->forge->createTable('payments', true);
    }

    private function createPaymentAllocationsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'payment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'due_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'payment_id', 'due_item_id'], 'uq_payment_allocations_company_payment_due_item');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_payment_allocations_company_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'CASCADE', 'RESTRICT', 'fk_payment_allocations_payment_id');
        $this->forge->addForeignKey('due_item_id', 'due_items', 'id', 'CASCADE', 'RESTRICT', 'fk_payment_allocations_due_item_id');
        $this->forge->createTable('payment_allocations', true);
    }

    private function createPaymentEventsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'payment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'event_key' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'payload_json' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'received'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addStandardFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'provider', 'event_key'], 'uq_payment_events_company_provider_event_key');
        $this->forge->addKey(['company_id', 'provider', 'status'], false, false, 'idx_payment_events_company_provider_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_payment_events_company_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'SET NULL', 'RESTRICT', 'fk_payment_events_payment_id');
        $this->forge->createTable('payment_events', true);
    }
}
