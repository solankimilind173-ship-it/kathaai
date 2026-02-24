<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates one permanent super admin for the application.
     */
    public function run(): void
    {
        $email = 'superadmin@kathaai.in';

        if (User::where('email', $email)->exists()) {
            return;
        }

        User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'super_admin',
        ]);
    }
}
