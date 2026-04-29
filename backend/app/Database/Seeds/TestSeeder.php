<?php

namespace App\Database\Seeds;

class TestSeeder extends BaseAppSeeder
{
    public function run(): void
    {
        // Test ortaminda da baz RBAC kurulumunu tekrar kullanir.
        $this->call(ProductionSeeder::class);
    }
}
