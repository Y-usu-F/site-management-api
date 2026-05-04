<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepositManagementTables extends Migration
{
    public function up()
    {
        $this->createDeposits();
        $this->createDepositTransactions();
    }

    public function down()
    {
        $this->forge->dropTable('deposit_transactions', true);
        $this->forge->dropTable('deposits', true);
    }

    private function addAuditFields(bool $withCreatedBy = true): void
    {
        $fields = [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ];
        if ($withCreatedBy) {
            $fields['created_by'] = ['type' => 'INT', 'unsigned' => true, 'null' => true];
        }
        $this->forge->addField($fields);
    }

    private function createDeposits(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'deposit_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'initial_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'balance_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'received_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'deposit_no'], 'uq_deposits_company_deposit_no');
        $this->forge->addKey(['company_id', 'site_id', 'unit_id', 'resident_profile_id', 'status'], false, false, 'idx_deposits_company_scope_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_deposits_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_deposits_site');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'CASCADE', 'RESTRICT', 'fk_deposits_unit');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'CASCADE', 'RESTRICT', 'fk_deposits_resident');
        $this->forge->createTable('deposits', true);
    }

    private function createDepositTransactions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'deposit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'transaction_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
            'due_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'payment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'transaction_date' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'deposit_id', 'transaction_type', 'transaction_date'], false, false, 'idx_deposit_tx_company_deposit_type_date');
        $this->forge->addKey(['company_id', 'due_item_id'], false, false, 'idx_deposit_tx_company_due_item');
        $this->forge->addKey(['company_id', 'payment_id'], false, false, 'idx_deposit_tx_company_payment');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_deposit_tx_company');
        $this->forge->addForeignKey('deposit_id', 'deposits', 'id', 'CASCADE', 'RESTRICT', 'fk_deposit_tx_deposit');
        $this->forge->addForeignKey('due_item_id', 'due_items', 'id', 'SET NULL', 'RESTRICT', 'fk_deposit_tx_due_item');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'SET NULL', 'RESTRICT', 'fk_deposit_tx_payment');
        $this->forge->createTable('deposit_transactions', true);
    }
}
