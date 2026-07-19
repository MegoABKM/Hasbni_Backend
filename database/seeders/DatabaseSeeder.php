<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Add this line to execute your SaaS seeder
        $this->call([
            SaaSDatabaseSeeder::class,
        ]);
    }
}