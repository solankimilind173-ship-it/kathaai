<?php

namespace Database\Seeders;

use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds subscription plans (Starter, Pro, Enterprise) and their features.
     * Prices are in INR (Indian Rupees).
     */
    public function run(): void
    {
        $tiers = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price' => 1499,
                'yearly_price' => 14999,
                'monthly_credits' => 500,
                'credit_rollover' => false,
                'is_active' => true,
                'features' => [
                    'voice_languages' => 'single',
                    'max_export_resolution' => '720p',
                    'allow_background_music' => '0',
                    'allow_intro_song_generation' => '0',
                    'max_episodes_per_project' => '5',
                    'priority_rendering_queue' => '0',
                    'max_dubbing_languages' => '1',
                    'allow_4k' => '0',
                    'allow_voice_style_selection' => '0',
                    'allow_multiple_video_styles' => '0',
                    'max_reels_per_episode' => '1',
                    'max_video_minutes' => '5',
                    'max_projects' => '5',
                ],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price' => 4999,
                'yearly_price' => 49999,
                'monthly_credits' => 2000,
                'credit_rollover' => true,
                'is_active' => true,
                'features' => [
                    'voice_languages' => 'multi',
                    'max_export_resolution' => '1080p',
                    'allow_background_music' => '1',
                    'allow_intro_song_generation' => '1',
                    'max_episodes_per_project' => '20',
                    'priority_rendering_queue' => '0',
                    'max_dubbing_languages' => '5',
                    'allow_4k' => '1',
                    'allow_voice_style_selection' => '1',
                    'allow_multiple_video_styles' => '1',
                    'max_reels_per_episode' => '5',
                    'max_video_minutes' => '30',
                    'max_projects' => '20',
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 14999,
                'yearly_price' => 149999,
                'monthly_credits' => 10000,
                'credit_rollover' => true,
                'is_active' => true,
                'features' => [
                    'voice_languages' => 'unlimited',
                    'max_export_resolution' => '4k',
                    'allow_background_music' => '1',
                    'allow_intro_song_generation' => '1',
                    'max_episodes_per_project' => '999',
                    'priority_rendering_queue' => '1',
                    'max_dubbing_languages' => '99',
                    'allow_4k' => '1',
                    'allow_voice_style_selection' => '1',
                    'allow_multiple_video_styles' => '1',
                    'max_reels_per_episode' => '10',
                    'max_video_minutes' => '120',
                    'max_projects' => '999',
                ],
            ],
        ];

        $defs = FeatureDefinition::all()->keyBy('key');

        foreach ($tiers as $t) {
            $features = $t['features'];
            unset($t['features']);

            $slug = $t['slug'];
            $t['stripe_price_id'] = config("services.stripe.plans.{$slug}.monthly");
            $t['stripe_yearly_price_id'] = config("services.stripe.plans.{$slug}.yearly");

            $plan = Plan::updateOrCreate(
                ['slug' => $slug],
                $t
            );

            foreach ($features as $key => $value) {
                if (! $defs->has($key)) {
                    continue;
                }
                PlanFeature::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_definition_id' => $defs->get($key)->id,
                    ],
                    ['value' => (string) $value]
                );
            }
        }
    }
}
