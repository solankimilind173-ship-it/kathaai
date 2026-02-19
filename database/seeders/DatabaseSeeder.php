<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Order: users → admin → reference data (languages, features, plans).
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(FeatureDefinitionSeeder::class);
        $this->call(PlanSeeder::class);
    }
}
