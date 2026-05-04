<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationInfrastructureTables extends Migration
{
    public function up()
    {
        $this->createNotificationTemplates();
        $this->createNotificationMessages();
        $this->createNotificationRecipients();
        $this->createNotificationDeliveryLogs();
        $this->createCommunicationProviders();
    }

    public function down()
    {
        $this->forge->dropTable('communication_providers', true);
        $this->forge->dropTable('notification_delivery_logs', true);
        $this->forge->dropTable('notification_recipients', true);
        $this->forge->dropTable('notification_messages', true);
        $this->forge->dropTable('notification_templates', true);
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

    private function createNotificationTemplates(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 20],
            'locale' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'tr'],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'body' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'code', 'channel', 'locale'], 'uq_ntf_templates_company_code_channel_locale');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_ntf_templates_company');
        $this->forge->createTable('notification_templates', true);
    }

    private function createNotificationMessages(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            'template_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 20],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'body' => ['type' => 'TEXT'],
            'payload_json' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'sent_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'channel', 'status', 'scheduled_at'], false, false, 'idx_ntf_messages_company_channel_status_sched');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_ntf_messages_company');
        $this->forge->addForeignKey('template_id', 'notification_templates', 'id', 'SET NULL', 'RESTRICT', 'fk_ntf_messages_template');
        $this->forge->createTable('notification_messages', true);
    }

    private function createNotificationRecipients(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            'message_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'message_id', 'status'], false, false, 'idx_ntf_recipients_company_message_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_ntf_recipients_company');
        $this->forge->addForeignKey('message_id', 'notification_messages', 'id', 'CASCADE', 'RESTRICT', 'fk_ntf_recipients_message');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_ntf_recipients_user');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_ntf_recipients_resident');
        $this->forge->createTable('notification_recipients', true);
    }

    private function createNotificationDeliveryLogs(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            'message_id' => ['type' => 'INT', 'unsigned' => true],
            'recipient_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 20],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'provider_reference' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'attempted_at' => ['type' => 'DATETIME'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'message_id', 'channel', 'status'], false, false, 'idx_ntf_logs_company_message_channel_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_ntf_logs_company');
        $this->forge->addForeignKey('message_id', 'notification_messages', 'id', 'CASCADE', 'RESTRICT', 'fk_ntf_logs_message');
        $this->forge->addForeignKey('recipient_id', 'notification_recipients', 'id', 'SET NULL', 'RESTRICT', 'fk_ntf_logs_recipient');
        $this->forge->createTable('notification_delivery_logs', true);
    }

    private function createCommunicationProviders(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 20],
            'provider_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'config_json' => ['type' => 'JSON', 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'channel', 'is_default', 'status'], false, false, 'idx_comm_providers_company_channel_default_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_comm_providers_company');
        $this->forge->createTable('communication_providers', true);
    }
}
