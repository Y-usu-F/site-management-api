<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use RuntimeException;
use Throwable;

/**
 * Development-only admin user + tenant bootstrap (company id=1).
 *
 * Idempotent: safe to run multiple times.
 *
 * Usage (development / local — never in production):
 *
 *   php spark db:seed DevAdminSeeder
 *
 * Prerequisites: migrations applied; for full RBAC links run {@see RbacSeeder} first.
 */
final class DevAdminSeeder extends Seeder
{
    private const DEV_EMAIL = 'admin@test.com';

    private const DEV_PASSWORD_PLAIN = 'Admin1234';

    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException(
                'DevAdminSeeder must not run in production (ENVIRONMENT=production).',
            );
        }

        if (! $this->databaseHasTable('companies')) {
            CLI::write('DevAdminSeeder: companies table missing — migrate first.', 'yellow');

            return;
        }

        try {
            $this->ensureCompanyIdOne();
            $roleId = $this->resolveOrCreatePrivilegedRole();
            $this->ensureRoleHasPermissionsIfEmpty($roleId);
            $userId = $this->upsertDevAdminUser();
            $this->upsertUserRoleAssignment(1, $userId, $roleId);

            try {
                cache()->clean();
            } catch (Throwable) {
                // Non-fatal when cache driver unavailable during CLI seed.
            }

            CLI::write(
                sprintf('DevAdminSeeder: OK — user %s @ company_id=1 (user_id=%d, role_id=%d).', self::DEV_EMAIL, $userId, $roleId),
                'green',
            );
        } catch (Throwable $e) {
            CLI::write('DevAdminSeeder failed: ' . $e->getMessage(), 'red');
            throw $e;
        }
    }

    /**
     * Ensures a row with id=1 exists for local multi-tenant defaults.
     */
    private function ensureCompanyIdOne(): void
    {
        $builder = $this->db->table('companies');
        $existing = $builder->where('id', 1)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        if ($existing !== null) {
            return;
        }

        $builder->insert([
            'id'         => 1,
            'public_id'  => $this->uuidV4(),
            'name'       => 'Development Company',
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function resolveOrCreatePrivilegedRole(): int
    {
        foreach (['super_admin', 'admin'] as $code) {
            $row = $this->db->table('roles')
                ->where('code', $code)
                ->where('company_id', null)
                ->get()
                ->getRowArray();
            if ($row !== null) {
                return (int) $row['id'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('roles')->insert([
            'company_id' => null,
            'code'       => 'admin',
            'name'       => 'Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * If the chosen role has no active role_permissions rows, attach every active permission row.
     * (Mirrors broad access when RbacSeeder has not run yet.)
     */
    private function ensureRoleHasPermissionsIfEmpty(int $roleId): void
    {
        if (! $this->databaseHasTable('role_permissions') || ! $this->databaseHasTable('permissions')) {
            return;
        }

        $builder = $this->db->table('role_permissions')->where('role_id', $roleId);
        if ($this->rolePermissionsHasDeletedAtColumn()) {
            $builder->where('deleted_at', null);
        }

        if ((int) $builder->countAllResults() > 0) {
            return;
        }

        $permQuery = $this->db->table('permissions')
            ->select('id')
            ->where('is_active', 1)
            ->where('deleted_at', null);

        $permissionIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $permQuery->get()->getResultArray(),
        );

        $now = date('Y-m-d H:i:s');
        foreach ($permissionIds as $permissionId) {
            $rp = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId);

            if ($this->rolePermissionsHasDeletedAtColumn()) {
                $rp->where('deleted_at', null);
            }

            $existing = $rp->get()->getRowArray();

            if ($existing !== null) {
                $update = [
                    'is_active'  => 1,
                    'updated_at' => $now,
                ];
                if ($this->rolePermissionsHasDeletedAtColumn()) {
                    $update['deleted_at'] = null;
                }
                $this->db->table('role_permissions')->where('id', (int) $existing['id'])->update($update);

                continue;
            }

            $insert = [
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
            if ($this->rolePermissionsHasIsActiveColumn()) {
                $insert['is_active'] = 1;
            }

            $this->db->table('role_permissions')->insert($insert);
        }
    }

    private function upsertDevAdminUser(): int
    {
        $email = strtolower(self::DEV_EMAIL);
        $hash = password_hash(self::DEV_PASSWORD_PLAIN, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        $row = $this->db->table('users')
            ->where('company_id', 1)
            ->where('email', $email)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($row !== null) {
            $this->db->table('users')->where('id', (int) $row['id'])->update([
                'password_hash'       => $hash,
                'is_active'           => 1,
                'status'              => 'active',
                'failed_login_count'  => 0,
                'locked_until'        => null,
                'first_name'          => 'Dev',
                'last_name'           => 'Admin',
                'updated_at'          => $now,
            ]);

            return (int) $row['id'];
        }

        $this->db->table('users')->insert([
            'company_id'      => 1,
            'public_id'       => $this->uuidV4(),
            'email'           => $email,
            'password_hash'   => $hash,
            'first_name'      => 'Dev',
            'last_name'       => 'Admin',
            'status'          => 'active',
            'is_active'       => 1,
            'failed_login_count' => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function upsertUserRoleAssignment(int $companyId, int $userId, int $roleId): void
    {
        $builder = $this->db->table('user_roles')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('role_id', $roleId);

        $existing = $builder->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        $patch = [
            'is_active'     => 1,
            'role_version'  => 1,
            'updated_at'    => $now,
        ];
        if ($this->userRolesHasDeletedAtColumn()) {
            $patch['deleted_at'] = null;
        }

        if ($existing !== null) {
            $this->db->table('user_roles')->where('id', (int) $existing['id'])->update($patch);

            return;
        }

        $insert = [
            'company_id'    => $companyId,
            'user_id'       => $userId,
            'role_id'       => $roleId,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];
        if ($this->userRolesHasIsActiveColumn()) {
            $insert['is_active'] = 1;
        }
        if ($this->userRolesHasRoleVersionColumn()) {
            $insert['role_version'] = 1;
        }

        $this->db->table('user_roles')->insert($insert);
    }

    private function databaseHasTable(string $table): bool
    {
        try {
            $prefix = $this->db->DBPrefix ?? '';
            $fullName = $prefix . $table;

            if ($this->db->DBDriver === 'SQLite3') {
                $row = $this->db->query(
                    'SELECT name FROM sqlite_master WHERE type = ? AND name = ?',
                    ['table', $fullName],
                )->getRowArray();

                return is_array($row);
            }

            $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
            $row = $this->db->table('information_schema.tables')
                ->select('TABLE_NAME')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $fullName)
                ->get(1)
                ->getRowArray();

            return is_array($row);
        } catch (Throwable) {
            return false;
        }
    }

    private function rolePermissionsHasDeletedAtColumn(): bool
    {
        static $cache;

        return $cache ??= $this->columnExists('role_permissions', 'deleted_at');
    }

    private function rolePermissionsHasIsActiveColumn(): bool
    {
        static $cache;

        return $cache ??= $this->columnExists('role_permissions', 'is_active');
    }

    private function userRolesHasDeletedAtColumn(): bool
    {
        static $cache;

        return $cache ??= $this->columnExists('user_roles', 'deleted_at');
    }

    private function userRolesHasIsActiveColumn(): bool
    {
        static $cache;

        return $cache ??= $this->columnExists('user_roles', 'is_active');
    }

    private function userRolesHasRoleVersionColumn(): bool
    {
        static $cache;

        return $cache ??= $this->columnExists('user_roles', 'role_version');
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            if ($this->db->DBDriver === 'SQLite3') {
                $prefix = $this->db->DBPrefix ?? '';
                $rows = $this->db->query('PRAGMA table_info(' . $this->db->escapeIdentifiers($prefix . $table) . ')')->getResultArray();
                foreach ($rows as $row) {
                    if (($row['name'] ?? '') === $column) {
                        return true;
                    }
                }

                return false;
            }

            $database = method_exists($this->db, 'getDatabase') ? (string) $this->db->getDatabase() : '';
            $prefix = $this->db->DBPrefix ?? '';
            $row = $this->db->table('information_schema.columns')
                ->select('COLUMN_NAME')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $prefix . $table)
                ->where('COLUMN_NAME', $column)
                ->get(1)
                ->getRowArray();

            return is_array($row);
        } catch (Throwable) {
            return false;
        }
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }
}
