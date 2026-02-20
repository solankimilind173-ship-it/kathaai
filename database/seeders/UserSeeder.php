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

        $plan = Plan::where('slug', 'enterprise')->first()
            ?? Plan::where('is_active', true)->orderByDesc('price')->first();
        $planId = $plan?->id;
        $credits = 50_000; // Generous testing balance (Enterprise has 10k/month)
        if ($planId === null) {
            $this->command?->warn('UserSeeder: No plan found. Run FeatureDefinitionSeeder and PlanSeeder first. Demo user will have plan_id=null.');
        }

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
