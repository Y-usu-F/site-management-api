<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateBlocksUniqueIndexForSoftDelete extends Migration
{
    public function up()
    {
        if (! $this->tableExists('blocks')) {
            return;
        }

        // Old index may exist from previous schema state.
        $this->dropIndexIfExists('blocks', 'uq_blocks_company_site_code');

        // Keep uniqueness for active records while allowing soft-deleted duplicates.
        $this->createUniqueIndexIfNotExists(
            'blocks',
            'uq_blocks_company_site_code_active',
            ['company_id', 'site_id', 'code', 'deleted_at']
        );
    }

    public function down()
    {
        if (! $this->tableExists('blocks')) {
            return;
        }

        $this->dropIndexIfExists('blocks', 'uq_blocks_company_site_code_active');

        // Old 3-column unique index cannot be recreated if historical soft-deleted duplicates exist.
        if (! $this->hasDuplicateActiveOrDeletedCodeTriples('blocks')) {
            $this->createUniqueIndexIfNotExists(
                'blocks',
                'uq_blocks_company_site_code',
                ['company_id', 'site_id', 'code']
            );
        }
    }

    private function createUniqueIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $tableName = $this->db->prefixTable($table);
        $columnsSql = implode(
            ', ',
            array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns)
        );

        $sql = sprintf(
            'CREATE UNIQUE INDEX %s ON %s (%s)',
            $this->quoteIdentifier($indexName),
            $this->quoteIdentifier($tableName),
            $columnsSql
        );
        $this->db->query($sql);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        $tableName = $this->db->prefixTable($table);
        $driver = $this->db->DBDriver;

        if ($driver === 'MySQLi') {
            $sql = sprintf(
                'ALTER TABLE %s DROP INDEX %s',
                $this->quoteIdentifier($tableName),
                $this->quoteIdentifier($indexName)
            );
            $this->db->query($sql);
            return;
        }

        $sql = sprintf('DROP INDEX %s', $this->quoteIdentifier($indexName));
        $this->db->query($sql);
    }

    private function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = $this->db->DBDriver;
        $tableName = $this->db->prefixTable($table);

        if ($driver === 'MySQLi') {
            $sql = 'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1';
            $row = $this->db->query($sql, [$tableName, $indexName])->getRowArray();
            return $row !== null;
        }

        if ($driver === 'Postgre') {
            $sql = 'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ? LIMIT 1';
            $row = $this->db->query($sql, [$tableName, $indexName])->getRowArray();
            return $row !== null;
        }

        if ($driver === 'SQLite3') {
            $sql = sprintf('PRAGMA index_list(%s)', $this->quoteIdentifier($tableName));
            $rows = $this->db->query($sql)->getResultArray();
            foreach ($rows as $row) {
                if ((string) ($row['name'] ?? '') === $indexName) {
                    return true;
                }
            }
            return false;
        }

        try {
            $sql = sprintf('SHOW INDEX FROM %s', $this->quoteIdentifier($tableName));
            $rows = $this->db->query($sql)->getResultArray();
            foreach ($rows as $row) {
                if ((string) ($row['Key_name'] ?? '') === $indexName) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function hasDuplicateActiveOrDeletedCodeTriples(string $table): bool
    {
        $tableName = $this->db->prefixTable($table);
        $sql = sprintf(
            'SELECT 1 FROM %s GROUP BY company_id, site_id, code HAVING COUNT(*) > 1 LIMIT 1',
            $this->quoteIdentifier($tableName)
        );
        $row = $this->db->query($sql)->getRowArray();
        return $row !== null;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return $this->db->protectIdentifiers($identifier, true);
    }
}
