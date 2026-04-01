<?php

namespace Database\Seeders;

use App\Models\FeatureDefinition;
use Illuminate\Database\Seeder;

class FeatureDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds feature definitions used by plans (voice, resolution, limits, etc.).
     */
    public function run(): void
    {
        $features = [
            ['key' => 'voice_languages', 'label' => 'Voice languages', 'type' => 'select', 'options' => ['single' => 'Single language', 'multi' => 'Multi-language', 'unlimited' => 'Unlimited languages'], 'sort_order' => 10],
            ['key' => 'max_export_resolution', 'label' => 'Max export resolution', 'type' => 'select', 'options' => ['720p' => '720p', '1080p' => '1080p', '4k' => '4K'], 'sort_order' => 20],
            ['key' => 'allow_background_music', 'label' => 'Background music', 'type' => 'boolean', 'options' => null, 'sort_order' => 30],
            ['key' => 'allow_intro_song_generation', 'label' => 'Intro song generation', 'type' => 'boolean', 'options' => null, 'sort_order' => 40],
            ['key' => 'max_episodes_per_project', 'label' => 'Max episodes per project', 'type' => 'integer', 'options' => null, 'sort_order' => 50],
            ['key' => 'max_episodes_per_day', 'label' => 'Max episode generations per day', 'type' => 'integer', 'options' => null, 'sort_order' => 55],
            ['key' => 'priority_rendering_queue', 'label' => 'Priority rendering queue', 'type' => 'boolean', 'options' => null, 'sort_order' => 60],
            ['key' => 'max_dubbing_languages', 'label' => 'Max dubbing languages', 'type' => 'integer', 'options' => null, 'sort_order' => 70],
            ['key' => 'allow_4k', 'label' => 'Allow 4K export', 'type' => 'boolean', 'options' => null, 'sort_order' => 80],
            ['key' => 'allow_voice_style_selection', 'label' => 'Voice style selection', 'type' => 'boolean', 'options' => null, 'sort_order' => 90],
            ['key' => 'allow_multiple_video_styles', 'label' => 'Multiple video styles', 'type' => 'boolean', 'options' => null, 'sort_order' => 100],
            ['key' => 'allow_trailer_generation', 'label' => 'Trailer generation', 'type' => 'boolean', 'options' => null, 'sort_order' => 105],
            ['key' => 'max_reels_per_episode', 'label' => 'Max reels per episode', 'type' => 'integer', 'options' => null, 'sort_order' => 110],
            ['key' => 'max_video_minutes', 'label' => 'Max video minutes', 'type' => 'integer', 'options' => null, 'sort_order' => 120],
            ['key' => 'max_projects', 'label' => 'Max projects', 'type' => 'integer', 'options' => null, 'sort_order' => 130],
        ];

        foreach ($features as $f) {
            FeatureDefinition::updateOrCreate(
                ['key' => $f['key']],
                $f
            );
        }
    }
}
