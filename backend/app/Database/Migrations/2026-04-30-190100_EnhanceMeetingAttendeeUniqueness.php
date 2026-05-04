<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class EnhanceMeetingAttendeeUniqueness extends Migration
{
    public function up()
    {
        if (! $this->tableExists('meeting_attendees')) {
            return;
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $table = $prefix . 'meeting_attendees';

        if (! $this->fieldExists('meeting_attendees', 'resident_profile_key')) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN resident_profile_key INT UNSIGNED GENERATED ALWAYS AS (COALESCE(resident_profile_id,0)) STORED");
        }
        if (! $this->fieldExists('meeting_attendees', 'unit_key')) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN unit_key INT UNSIGNED GENERATED ALWAYS AS (COALESCE(unit_id,0)) STORED");
        }

        // MySQL-safe dedup key: active rows => 'active', inactive rows => NULL
        if (! $this->fieldExists('meeting_attendees', 'active_unique_key')) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN active_unique_key VARCHAR(10) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL AND status <> 'cancelled' THEN 'active' ELSE NULL END) STORED");
        }

        if ($this->indexExists('meeting_attendees', 'uq_meeting_attendees_active_dedup')) {
            $this->db->query("DROP INDEX uq_meeting_attendees_active_dedup ON {$table}");
        }
        $this->db->query("CREATE UNIQUE INDEX uq_meeting_attendees_active_dedup ON {$table} (company_id, meeting_id, resident_profile_key, unit_key, attendance_type, active_unique_key)");
    }

    public function down()
    {
        if (! $this->tableExists('meeting_attendees')) {
            return;
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $table = $prefix . 'meeting_attendees';

        if ($this->indexExists('meeting_attendees', 'uq_meeting_attendees_active_dedup')) {
            try {
                $this->db->query("DROP INDEX uq_meeting_attendees_active_dedup ON {$table}");
            } catch (Throwable) {
                // Ignore when index is already absent.
            }
        }
        if ($this->fieldExists('meeting_attendees', 'active_unique_key')) {
            try {
                $this->db->query("ALTER TABLE {$table} DROP COLUMN active_unique_key");
            } catch (Throwable) {
                // Ignore when column is already absent.
            }
        }
        if ($this->fieldExists('meeting_attendees', 'unit_key')) {
            try {
                $this->db->query("ALTER TABLE {$table} DROP COLUMN unit_key");
            } catch (Throwable) {
                // Ignore when column is already absent.
            }
        }
        if ($this->fieldExists('meeting_attendees', 'resident_profile_key')) {
            try {
                $this->db->query("ALTER TABLE {$table} DROP COLUMN resident_profile_key");
            } catch (Throwable) {
                // Ignore when column is already absent.
            }
        }
    }

    private function tableExists(string $table): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $row = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table])->getRowArray();
            return is_array($row);
        }
        $row = $this->db->query('SHOW TABLES LIKE ?', [$table])->getRowArray();
        return is_array($row);
    }

    private function fieldExists(string $table, string $field): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA table_info('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $field) {
                    return true;
                }
            }
            return false;
        }
        $row = $this->db->query("SHOW COLUMNS FROM {$table} LIKE ?", [$field])->getRowArray();
        return is_array($row);
    }

    private function indexExists(string $table, string $index): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA index_list('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $index) {
                    return true;
                }
            }
            return false;
        }

        try {
            $row = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index])->getRowArray();
            return is_array($row);
        } catch (Throwable) {
            return false;
        }
    }
}
