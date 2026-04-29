<?php

namespace App\Database\Seeds;

use Throwable;

class StagingSeeder extends BaseAppSeeder
{
    public function run(): void
    {
        $name = static::class;
        $this->logStart($name);

        try {
            $this->call(ProductionSeeder::class);
            $this->logSuccess($name, 'production baseline seeded for staging');
        } catch (Throwable $e) {
            $this->logFailure($name, $e->getMessage());
            throw $e;
        }
    }
}
