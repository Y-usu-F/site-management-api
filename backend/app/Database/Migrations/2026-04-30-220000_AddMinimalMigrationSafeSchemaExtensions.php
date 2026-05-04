<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddMinimalMigrationSafeSchemaExtensions extends Migration
{
    public function up()
    {
        $this->addLegacyIdColumns();
        $this->addDueInterestFields();
        $this->addPaymentReconciliationField();
        $this->addOccupancySourceTracking();
        $this->addDepositReasonCodes();
        $this->addLegalExtraFields();
        $this->createMigrationQuarantineLogs();
    }

    public function down()
    {
        $this->dropLegalExtraFields();
        $this->dropDepositReasonCodes();
        $this->dropOccupancySourceTracking();
        $this->dropPaymentReconciliationField();
        $this->dropDueInterestFields();
        $this->dropLegacyIdColumns();
        $this->dropMigrationQuarantineLogs();
    }

    private function addLegacyIdColumns(): void
    {
        $tables = [
            'sites',
            'blocks',
            'units',
            'resident_profiles',
            'unit_occupancies',
            'due_items',
            'payments',
            'deposits',
            'service_requests',
            'legal_cases',
        ];

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            if (! $this->fieldExists($table, 'legacy_id')) {
                $this->forge->addColumn($table, [
                    'legacy_id' => [
                        'type' => 'BIGINT',
                        'null' => true,
                    ],
                ]);
            }
            $this->createIndexIfPossible($table, 'idx_' . $table . '_legacy_id', 'legacy_id');
        }
    }

    private function addDueInterestFields(): void
    {
        if (! $this->tableExists('due_items')) {
            return;
        }
        if (! $this->fieldExists('due_items', 'interest_amount')) {
            $this->forge->addColumn('due_items', [
                'interest_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => false,
                    'default' => '0.00',
                ],
            ]);
        }
        if (! $this->fieldExists('due_items', 'penalty_amount')) {
            $this->forge->addColumn('due_items', [
                'penalty_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'null' => false,
                    'default' => '0.00',
                ],
            ]);
        }
    }

    private function addPaymentReconciliationField(): void
    {
        if (! $this->tableExists('payments')) {
            return;
        }
        if (! $this->fieldExists('payments', 'reconciliation_batch_id')) {
            $this->forge->addColumn('payments', [
                'reconciliation_batch_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
            ]);
        }
    }

    private function addOccupancySourceTracking(): void
    {
        if (! $this->tableExists('unit_occupancies')) {
            return;
        }
        if (! $this->fieldExists('unit_occupancies', 'source_type')) {
            $this->forge->addColumn('unit_occupancies', [
                'source_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }
        if (! $this->fieldExists('unit_occupancies', 'source_legacy_id')) {
            $this->forge->addColumn('unit_occupancies', [
                'source_legacy_id' => [
                    'type' => 'BIGINT',
                    'null' => true,
                ],
            ]);
        }
        $this->createIndexIfPossible('unit_occupancies', 'idx_unit_occupancies_source_legacy_id', 'source_legacy_id');
    }

    private function addDepositReasonCodes(): void
    {
        if (! $this->tableExists('deposit_transactions')) {
            return;
        }
        if (! $this->fieldExists('deposit_transactions', 'refund_reason_code')) {
            $this->forge->addColumn('deposit_transactions', [
                'refund_reason_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }
        if (! $this->fieldExists('deposit_transactions', 'deduction_reason_code')) {
            $this->forge->addColumn('deposit_transactions', [
                'deduction_reason_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }
    }

    private function addLegalExtraFields(): void
    {
        if (! $this->tableExists('legal_cases')) {
            return;
        }
        if (! $this->fieldExists('legal_cases', 'court_name')) {
            $this->forge->addColumn('legal_cases', [
                'court_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
            ]);
        }
        if (! $this->fieldExists('legal_cases', 'hearing_date')) {
            $this->forge->addColumn('legal_cases', [
                'hearing_date' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
        if (! $this->fieldExists('legal_cases', 'enforcement_stage_code')) {
            $this->forge->addColumn('legal_cases', [
                'enforcement_stage_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }
    }

    private function createMigrationQuarantineLogs(): void
    {
        if ($this->tableExists('migration_quarantine_logs')) {
            return;
        }
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'legacy_table' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'legacy_id' => ['type' => 'BIGINT', 'null' => true],
            'payload_json' => ['type' => 'JSON', 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['legacy_table', 'legacy_id'], false, false, 'idx_migration_quarantine_legacy');
        $this->forge->addKey(['entity_type', 'created_at'], false, false, 'idx_migration_quarantine_entity_created');
        $this->forge->createTable('migration_quarantine_logs', true);
    }

    private function dropLegacyIdColumns(): void
    {
        $tables = [
            'sites',
            'blocks',
            'units',
            'resident_profiles',
            'unit_occupancies',
            'due_items',
            'payments',
            'deposits',
            'service_requests',
            'legal_cases',
        ];
        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            $this->dropIndexIfPossible($table, 'idx_' . $table . '_legacy_id');
            if ($this->fieldExists($table, 'legacy_id')) {
                $this->safeDropColumn($table, 'legacy_id');
            }
        }
    }

    private function dropDueInterestFields(): void
    {
        if (! $this->tableExists('due_items')) {
            return;
        }
        if ($this->fieldExists('due_items', 'interest_amount')) {
            $this->safeDropColumn('due_items', 'interest_amount');
        }
        if ($this->fieldExists('due_items', 'penalty_amount')) {
            $this->safeDropColumn('due_items', 'penalty_amount');
        }
    }

    private function dropPaymentReconciliationField(): void
    {
        if ($this->tableExists('payments') && $this->fieldExists('payments', 'reconciliation_batch_id')) {
            $this->safeDropColumn('payments', 'reconciliation_batch_id');
        }
    }

    private function dropOccupancySourceTracking(): void
    {
        if (! $this->tableExists('unit_occupancies')) {
            return;
        }
        $this->dropIndexIfPossible('unit_occupancies', 'idx_unit_occupancies_source_legacy_id');
        if ($this->fieldExists('unit_occupancies', 'source_type')) {
            $this->safeDropColumn('unit_occupancies', 'source_type');
        }
        if ($this->fieldExists('unit_occupancies', 'source_legacy_id')) {
            $this->safeDropColumn('unit_occupancies', 'source_legacy_id');
        }
    }

    private function dropDepositReasonCodes(): void
    {
        if (! $this->tableExists('deposit_transactions')) {
            return;
        }
        if ($this->fieldExists('deposit_transactions', 'refund_reason_code')) {
            $this->safeDropColumn('deposit_transactions', 'refund_reason_code');
        }
        if ($this->fieldExists('deposit_transactions', 'deduction_reason_code')) {
            $this->safeDropColumn('deposit_transactions', 'deduction_reason_code');
        }
    }

    private function dropLegalExtraFields(): void
    {
        if (! $this->tableExists('legal_cases')) {
            return;
        }
        if ($this->fieldExists('legal_cases', 'court_name')) {
            $this->safeDropColumn('legal_cases', 'court_name');
        }
        if ($this->fieldExists('legal_cases', 'hearing_date')) {
            $this->safeDropColumn('legal_cases', 'hearing_date');
        }
        if ($this->fieldExists('legal_cases', 'enforcement_stage_code')) {
            $this->safeDropColumn('legal_cases', 'enforcement_stage_code');
        }
    }

    private function dropMigrationQuarantineLogs(): void
    {
        if ($this->tableExists('migration_quarantine_logs')) {
            $this->forge->dropTable('migration_quarantine_logs', true);
        }
    }

    private function safeDropColumn(string $table, string $column): void
    {
        try {
            $this->forge->dropColumn($table, $column);
        } catch (Throwable) {
            // Keep down() idempotent when field already removed in prior cycle.
        }
    }

    private function tableExists(string $table): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $row = $this->db->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
                [$table]
            )->getRowArray();
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

    private function createIndexIfPossible(string $table, string $indexName, string $column): void
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$tableName} ({$column})");
        } catch (Throwable) {
            // Keep migration idempotent across reruns/environments.
        }
    }

    private function dropIndexIfPossible(string $table, string $indexName): void
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$tableName}");
        } catch (Throwable) {
            // Ignore when index is absent.
        }
    }
}
