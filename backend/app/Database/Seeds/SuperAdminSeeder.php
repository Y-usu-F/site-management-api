<?php

namespace App\Database\Seeds;

use RuntimeException;
use Throwable;

class SuperAdminSeeder extends BaseAppSeeder
{
    /**
     * @return array{company_id:int,user_created:bool,role_linked:bool,email:string}
     */
    public function run(): array
    {
        $name = static::class;
        $this->logStart($name);

        try {
            $email = strtolower($this->requireEnv('SUPER_ADMIN_EMAIL'));
            $password = $this->requireEnv('SUPER_ADMIN_PASSWORD');

            $company = $this->ensureSystemCompany();
            $companyId = (int) ($company['id'] ?? 0);
            if ($companyId <= 0) {
                throw new RuntimeException('System company could not be resolved.');
            }

            $roleId = $this->getSuperAdminRoleId();

            $userCreated = false;
            $userId = $this->findUserId($companyId, $email);
            if ($userId === null) {
                $userId = $this->createSuperAdminUser($companyId, $email, $password);
                $userCreated = true;
            }

            $roleLinked = $this->linkRoleToUser($companyId, $userId, $roleId);

            $result = [
                'company_id'   => $companyId,
                'user_created' => $userCreated,
                'role_linked'  => $roleLinked,
                'email'        => $email,
            ];

            $this->logSuccess($name, json_encode($result, JSON_UNESCAPED_SLASHES));

            return $result;
        } catch (Throwable $e) {
            $this->logFailure($name, $e->getMessage());
            throw $e;
        }
    }

    private function getSuperAdminRoleId(): int
    {
        $role = $this->db->table('roles')
            ->where('company_id', null)
            ->where('code', 'super_admin')
            ->get()
            ->getRowArray();

        if ($role === null) {
            throw new RuntimeException('Role "super_admin" not found. Run RbacSeeder first.');
        }

        return (int) $role['id'];
    }

    private function findUserId(int $companyId, string $email): ?int
    {
        $user = $this->db->table('users')
            ->where('company_id', $companyId)
            ->where('email', $email)
            ->get()
            ->getRowArray();

        return $user !== null ? (int) $user['id'] : null;
    }

    private function createSuperAdminUser(int $companyId, string $email, string $password): int
    {
        $now = $this->now();
        $this->db->table('users')->insert([
            'company_id'    => $companyId,
            'public_id'     => $this->generateUuidV4(),
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name'    => 'Super',
            'last_name'     => 'Admin',
            'status'        => 'active',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function linkRoleToUser(int $companyId, int $userId, int $roleId): bool
    {
        $builder = $this->db->table('user_roles');
        $existing = $builder
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            return false;
        }

        $now = $this->now();
        $builder->insert([
            'company_id' => $companyId,
            'user_id'    => $userId,
            'role_id'    => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return true;
    }
}
