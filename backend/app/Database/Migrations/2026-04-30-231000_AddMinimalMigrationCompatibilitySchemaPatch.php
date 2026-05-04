<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

class AddMinimalMigrationCompatibilitySchemaPatch extends Migration
{
    public function up()
    {
        $this->addPaymentReferenceNormalizationFields();
        $this->addImportLineageMetadataFields();
        $this->ensureDueInterestPenaltyCompatibility();
        $this->createResidentBalanceMovementsTable();
        $this->createLegalEventTypeMappingsTable();
    }

    public function down()
    {
        $this->dropLegalEventTypeMappingsTable();
        $this->dropResidentBalanceMovementsTable();
        $this->dropImportLineageMetadataFields();
        $this->dropPaymentReferenceNormalizationFields();
        $this->dropDueInterestPenaltyCompatibilityFields();
    }

    private function addPaymentReferenceNormalizationFields(): void
    {
        if (! $this->tableExists('payments')) {
            return;
        }

        if (! $this->fieldExists('payments', 'receipt_no')) {
            $this->forge->addColumn('payments', [
                'receipt_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
            ]);
        }

        if (! $this->fieldExists('payments', 'external_reference_no')) {
            $this->forge->addColumn('payments', [
                'external_reference_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
            ]);
        }

        if (! $this->fieldExists('payments', 'reference_source')) {
            $this->forge->addColumn('payments', [
                'reference_source' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
            ]);
        }
    }

    private function dropPaymentReferenceNormalizationFields(): void
    {
        if (! $this->tableExists('payments')) {
            return;
        }

        foreach (['receipt_no', 'external_reference_no', 'reference_source'] as $field) {
            if ($this->fieldExists('payments', $field)) {
                $this->safeDropColumn('payments', $field);
            }
        }
    }

    private function addImportLineageMetadataFields(): void
    {
        $tables = [
            'blocks',
            'units',
            'resident_profiles',
            'unit_occupancies',
            'due_definitions',
            'due_items',
            'payments',
            'deposits',
            'deposit_transactions',
            'legal_cases',
        ];

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            if (! $this->fieldExists($table, 'import_run_id')) {
                $this->forge->addColumn($table, [
                    'import_run_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => 100,
                        'null' => true,
                    ],
                ]);
            }

            if (! $this->fieldExists($table, 'source_hash')) {
                $this->forge->addColumn($table, [
                    'source_hash' => [
                        'type' => 'VARCHAR',
                        'constraint' => 128,
                        'null' => true,
                    ],
                ]);
            }
        }
    }

    private function dropImportLineageMetadataFields(): void
    {
        $tables = [
            'blocks',
            'units',
            'resident_profiles',
            'unit_occupancies',
            'due_definitions',
            'due_items',
            'payments',
            'deposits',
            'deposit_transactions',
            'legal_cases',
        ];

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            foreach (['import_run_id', 'source_hash'] as $field) {
                if ($this->fieldExists($table, $field)) {
                    $this->safeDropColumn($table, $field);
                }
            }
        }
    }

    private function ensureDueInterestPenaltyCompatibility(): void
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

    private function dropDueInterestPenaltyCompatibilityFields(): void
    {
        if (! $this->tableExists('due_items')) {
            return;
        }

        // Keep rollback safe for this migration's additive behavior.
        if ($this->fieldExists('due_items', 'interest_amount')) {
            $this->safeDropColumn('due_items', 'interest_amount');
        }
        if ($this->fieldExists('due_items', 'penalty_amount')) {
            $this->safeDropColumn('due_items', 'penalty_amount');
        }
    }

    private function safeDropColumn(string $table, string $column): void
    {
        try {
            $this->forge->dropColumn($table, $column);
        } catch (Throwable) {
            // Keep down() idempotent when schema drift exists in tests.
        }
    }

    private function createResidentBalanceMovementsTable(): void
    {
        if ($this->tableExists('resident_balance_movements')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
            'resident_profile_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'legacy_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'import_run_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'movement_date' => ['type' => 'DATETIME', 'null' => true],
            'movement_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'source_table' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'source_reference' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'debit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => '0.00'],
            'credit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => '0.00'],
            'balance_after' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'meta_json' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('resident_balance_movements', true);

        $this->createIndexIfPossible('resident_balance_movements', 'idx_rbm_company_id', 'company_id');
        $this->createIndexIfPossible('resident_balance_movements', 'idx_rbm_resident_profile_id', 'resident_profile_id');
        $this->createIndexIfPossible('resident_balance_movements', 'idx_rbm_unit_id', 'unit_id');
        $this->createIndexIfPossible('resident_balance_movements', 'idx_rbm_legacy_id', 'legacy_id');
        $this->createIndexIfPossible('resident_balance_movements', 'idx_rbm_import_run_id', 'import_run_id');
        $this->createIndexIfPossible('resident_balance_movements', 'idx_rbm_movement_date', 'movement_date');
        $this->createCompositeIndexIfPossible('resident_balance_movements', 'idx_rbm_company_resident_mdate', 'company_id, resident_profile_id, movement_date');
    }

    private function dropResidentBalanceMovementsTable(): void
    {
        if (! $this->tableExists('resident_balance_movements')) {
            return;
        }

        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_company_id');
        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_resident_profile_id');
        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_unit_id');
        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_legacy_id');
        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_import_run_id');
        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_movement_date');
        $this->dropIndexIfPossible('resident_balance_movements', 'idx_rbm_company_resident_mdate');

        $this->forge->dropTable('resident_balance_movements', true);
    }

    private function createLegalEventTypeMappingsTable(): void
    {
        if ($this->tableExists('legal_event_type_mappings')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'legacy_value' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'normalized_value' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'target_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('legal_event_type_mappings', true);

        $this->createIndexIfPossible('legal_event_type_mappings', 'idx_letm_legacy_value', 'legacy_value');
        $this->createIndexIfPossible('legal_event_type_mappings', 'idx_letm_normalized_value', 'normalized_value');
        $this->createIndexIfPossible('legal_event_type_mappings', 'idx_letm_target_type', 'target_type');
        $this->createCompositeIndexIfPossible('legal_event_type_mappings', 'idx_letm_company_legacy', 'company_id, legacy_value');
    }

    private function dropLegalEventTypeMappingsTable(): void
    {
        if (! $this->tableExists('legal_event_type_mappings')) {
            return;
        }

        $this->dropIndexIfPossible('legal_event_type_mappings', 'idx_letm_legacy_value');
        $this->dropIndexIfPossible('legal_event_type_mappings', 'idx_letm_normalized_value');
        $this->dropIndexIfPossible('legal_event_type_mappings', 'idx_letm_target_type');
        $this->dropIndexIfPossible('legal_event_type_mappings', 'idx_letm_company_legacy');

        $this->forge->dropTable('legal_event_type_mappings', true);
    }

    private function tableExists(string $table): bool
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $row = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table])->getRowArray();
            return is_array($row);
        }

        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $row = $this->db->table('information_schema.tables')
            ->select('TABLE_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->get(1)
            ->getRowArray();
        return is_array($row);
    }

    private function fieldExists(string $table, string $field): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        if ($this->db->DBDriver === 'SQLite3') {
            $rows = $this->db->query("PRAGMA table_info('{$table}')")->getResultArray();
            foreach ($rows as $row) {
                if (($row['name'] ?? null) === $field) {
                    return true;
                }
            }
            return false;
        }
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
        $row = $this->db->table('information_schema.columns')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $field)
            ->get(1)
            ->getRowArray();
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

    private function createCompositeIndexIfPossible(string $table, string $indexName, string $columns): void
    {
        $prefix = method_exists($this->db, 'getPrefix') ? (string) $this->db->getPrefix() : '';
        $tableName = $prefix . $table;
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$tableName} ({$columns})");
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

