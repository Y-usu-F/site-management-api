<?php

namespace App\Database\Seeds;

use Throwable;

class LocalSeeder extends BaseAppSeeder
{
    public function run(): void
    {
        $name = static::class;
        $this->logStart($name);

        try {
            // Local ortamda da yalnizca Faz 03 kapsamindaki RBAC + super admin verileri yuklenir.
            $this->call(ProductionSeeder::class);
            $this->logSuccess($name, 'production baseline seeded');
        } catch (Throwable $e) {
            $this->logFailure($name, $e->getMessage());
            throw $e;
        }
    }
}
