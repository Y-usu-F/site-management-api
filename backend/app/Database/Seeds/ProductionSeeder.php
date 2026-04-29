<?php

namespace App\Database\Seeds;

use RuntimeException;
use Throwable;

class ProductionSeeder extends BaseAppSeeder
{
    public function run(): void
    {
        $name = static::class;
        $this->logStart($name);

        try {
            $this->guardProductionFirstRun();
            $this->call(RbacSeeder::class);
            $this->call(SuperAdminSeeder::class);

            $this->logSuccess($name, 'rbac + super_admin seeded');
        } catch (Throwable $e) {
            $this->logFailure($name, $e->getMessage());
            throw $e;
        }
    }

    private function guardProductionFirstRun(): void
    {
        if (ENVIRONMENT !== 'production') {
            return;
        }

        $allowReseed = filter_var(env('ALLOW_PRODUCTION_RESEED', false), FILTER_VALIDATE_BOOL);
        if ($allowReseed) {
            return;
        }

        $alreadySeeded = $this->db->table('users')
            ->where('deleted_at', null)
            ->countAllResults() > 0;

        if ($alreadySeeded) {
            throw new RuntimeException('ProductionSeeder first-run guard: tekrar seed engellendi. ALLOW_PRODUCTION_RESEED=true olmadan calismaz.');
        }
    }
}
