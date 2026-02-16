<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('email', 'test@example.com')->exists()) {
            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'kathaai@mailinator.com',
            'password' => '12345678',
        ]);
    }
}
