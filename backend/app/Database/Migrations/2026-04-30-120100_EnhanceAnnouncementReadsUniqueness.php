<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class EnhanceAnnouncementReadsUniqueness extends Migration
{
    public function up()
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $table = $prefix . 'announcement_reads';
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        if ($database === '') {
            return;
        }

        $existsActorKey = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = 'actor_key'",
            [$database, $table]
        )->getRowArray();

        if ((int) ($existsActorKey['cnt'] ?? 0) === 0) {
            $this->db->query(
                "ALTER TABLE {$table}
                 ADD COLUMN actor_key VARCHAR(64)
                 GENERATED ALWAYS AS (
                   CASE
                     WHEN user_id IS NOT NULL THEN CONCAT('u:', user_id)
                     WHEN resident_profile_id IS NOT NULL THEN CONCAT('r:', resident_profile_id)
                     ELSE NULL
                   END
                 ) STORED"
            );
        }

        $idx = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = 'uq_announcement_reads_company_announcement_actor'",
            [$database, $table]
        )->getRowArray();
        if ((int) ($idx['cnt'] ?? 0) === 0) {
            $this->db->query("ALTER TABLE {$table} ADD UNIQUE KEY uq_announcement_reads_company_announcement_actor (company_id, announcement_id, actor_key)");
        }
    }

    public function down()
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $table = $prefix . 'announcement_reads';
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        if ($database === '') {
            return;
        }

        $idx = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = 'uq_announcement_reads_company_announcement_actor'",
            [$database, $table]
        )->getRowArray();
        if ((int) ($idx['cnt'] ?? 0) > 0) {
            try {
                $this->db->query("ALTER TABLE {$table} DROP INDEX uq_announcement_reads_company_announcement_actor");
            } catch (Throwable) {
                // Ignore to keep rollback idempotent.
            }
        }

        $existsActorKey = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = 'actor_key'",
            [$database, $table]
        )->getRowArray();
        if ((int) ($existsActorKey['cnt'] ?? 0) > 0) {
            try {
                $this->db->query("ALTER TABLE {$table} DROP COLUMN actor_key");
            } catch (Throwable) {
                // Ignore to keep rollback idempotent.
            }
        }
    }
}
