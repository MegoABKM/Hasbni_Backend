<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Add this line to execute your SaaS seeder
        $this->call([
            SaaSDatabaseSeeder::class,
            RbacSeeder::class,
        ]);
    }
}
