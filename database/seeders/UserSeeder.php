<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates/updates one development/demo user with full benefits (Enterprise plan + high credits).
     */
    public function run(): void
    {
        $email = 'kathaai@mailinator.com';

        $enterprise = Plan::where('slug', 'enterprise')->first();
        $planId = $enterprise?->id;
        $credits = 50_000; // Generous testing balance (Enterprise has 10k/month)

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'plan_id' => $planId,
                'credits' => $credits,
            ]);
            return;
        }

        User::create([
            'name' => 'Milind Solanki',
            'email' => $email,
            'password' => '12345678',
            'role' => 'user',
            'plan_id' => $planId,
            'credits' => $credits,
        ]);
    }
}
