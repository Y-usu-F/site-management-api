<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnnouncementTables extends Migration
{
    public function up()
    {
        $this->createAnnouncements();
        $this->createAnnouncementTargets();
        $this->createAnnouncementReads();
    }

    public function down()
    {
        $this->forge->dropTable('announcement_reads', true);
        $this->forge->dropTable('announcement_targets', true);
        $this->forge->dropTable('announcements', true);
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

    private function createAnnouncements(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'body' => ['type' => 'TEXT', 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'publish_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'status', 'publish_at', 'expires_at'], false, false, 'idx_announcements_company_status_publish_expire');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_announcements_company');
        $this->forge->createTable('announcements', true);
    }

    private function createAnnouncementTargets(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'announcement_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'target_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'target_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'announcement_id', 'target_type', 'target_id'], false, false, 'idx_announcement_targets_company_announcement_target');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_announcement_targets_company');
        $this->forge->addForeignKey('announcement_id', 'announcements', 'id', 'CASCADE', 'RESTRICT', 'fk_announcement_targets_announcement');
        $this->forge->createTable('announcement_targets', true);
    }

    private function createAnnouncementReads(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'announcement_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'read_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'announcement_id', 'user_id'], 'uq_announcement_reads_company_announcement_user');
        $this->forge->addUniqueKey(['company_id', 'announcement_id', 'resident_profile_id'], 'uq_announcement_reads_company_announcement_resident');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_announcement_reads_company');
        $this->forge->addForeignKey('announcement_id', 'announcements', 'id', 'CASCADE', 'RESTRICT', 'fk_announcement_reads_announcement');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'RESTRICT', 'fk_announcement_reads_user');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_announcement_reads_resident');
        $this->forge->createTable('announcement_reads', true);
    }
}
