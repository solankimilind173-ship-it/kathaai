<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic',
                'price' => 999,
                'max_projects' => 3,
                'max_dubbing_languages' => 1,
                'max_reels_per_episode' => 1,
                'max_video_minutes' => 5,
                'allow_multiple_video_styles' => false,
                'allow_4k' => false,
                'allow_voice_style_selection' => false,
                'allow_background_music' => true,
                'allow_intro_song_generation' => false,
                'monthly_credits' => 200,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price' => 2999,
                'max_projects' => 20,
                'max_dubbing_languages' => 5,
                'max_reels_per_episode' => 5,
                'max_video_minutes' => 30,
                'allow_multiple_video_styles' => true,
                'allow_4k' => true,
                'allow_voice_style_selection' => true,
                'allow_background_music' => true,
                'allow_intro_song_generation' => true,
                'monthly_credits' => 2000,
            ]
        );
    }
}
