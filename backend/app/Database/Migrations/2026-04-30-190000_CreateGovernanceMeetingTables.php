<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGovernanceMeetingTables extends Migration
{
    public function up()
    {
        $this->createMeetings();
        $this->createMeetingAgendaItems();
        $this->createMeetingAttendees();
        $this->createDecisionBookEntries();
    }

    public function down()
    {
        $this->forge->dropTable('decision_book_entries', true);
        $this->forge->dropTable('meeting_attendees', true);
        $this->forge->dropTable('meeting_agenda_items', true);
        $this->forge->dropTable('meetings', true);
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

    private function createMeetings(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'site_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'meeting_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'meeting_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 220, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'meeting_date' => ['type' => 'DATETIME', 'null' => false],
            'location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'locked_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'meeting_no'], 'uq_meetings_company_meeting_no');
        $this->forge->addKey(['company_id', 'site_id', 'status', 'meeting_date'], false, false, 'idx_meetings_company_site_status_date');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_meetings_company');
        $this->forge->addForeignKey('site_id', 'sites', 'id', 'CASCADE', 'RESTRICT', 'fk_meetings_site');
        $this->forge->createTable('meetings', true);
    }

    private function createMeetingAgendaItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'meeting_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'item_no' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 220, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'meeting_id', 'item_no'], 'uq_meeting_agenda_company_meeting_item');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_meeting_agenda_company');
        $this->forge->addForeignKey('meeting_id', 'meetings', 'id', 'CASCADE', 'RESTRICT', 'fk_meeting_agenda_meeting');
        $this->forge->createTable('meeting_agenda_items', true);
    }

    private function createMeetingAttendees(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'meeting_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'attendance_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'proxy_for_resident_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'land_share' => ['type' => 'DECIMAL', 'constraint' => '12,4', 'null' => true],
            'vote_weight' => ['type' => 'DECIMAL', 'constraint' => '12,4', 'null' => true],
            'signed_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'invited'],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'meeting_id', 'resident_profile_id', 'unit_id', 'status'], false, false, 'idx_meeting_attendees_company_scope_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_meeting_attendees_company');
        $this->forge->addForeignKey('meeting_id', 'meetings', 'id', 'CASCADE', 'RESTRICT', 'fk_meeting_attendees_meeting');
        $this->forge->addForeignKey('resident_profile_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_meeting_attendees_resident');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'SET NULL', 'RESTRICT', 'fk_meeting_attendees_unit');
        $this->forge->addForeignKey('proxy_for_resident_id', 'resident_profiles', 'id', 'SET NULL', 'RESTRICT', 'fk_meeting_attendees_proxy_for');
        $this->forge->createTable('meeting_attendees', true);
    }

    private function createDecisionBookEntries(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'meeting_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'decision_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'decision_date' => ['type' => 'DATE', 'null' => false],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 220, 'null' => false],
            'decision_text' => ['type' => 'TEXT', 'null' => false],
            'vote_result' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'locked_at' => ['type' => 'DATETIME', 'null' => true],
            'document_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->addAuditFields();
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'decision_no'], 'uq_decision_book_company_decision_no');
        $this->forge->addKey(['company_id', 'meeting_id', 'status', 'decision_date'], false, false, 'idx_decision_book_company_meeting_status_date');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'RESTRICT', 'fk_decision_book_company');
        $this->forge->addForeignKey('meeting_id', 'meetings', 'id', 'SET NULL', 'RESTRICT', 'fk_decision_book_meeting');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'SET NULL', 'RESTRICT', 'fk_decision_book_document');
        $this->forge->createTable('decision_book_entries', true);
    }
}
