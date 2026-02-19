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
        if (User::where('email', 'kathaai@mailinator.com')->exists()) {
            return;
        }

        User::factory()->create([
            'name' => 'Milind Solanki',
            'email' => 'kathaai@mailinator.com',
            'password' => '12345678',
            'role' => 'super_admin',
        ]);
    }
}
