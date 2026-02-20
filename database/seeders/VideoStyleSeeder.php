<?php

namespace Database\Seeders;

use App\Models\VideoStyle;
use Illuminate\Database\Seeder;

class VideoStyleSeeder extends Seeder
{
    /**
     * Seed the video_styles table with default styles and extra credit costs.
     */
    public function run(): void
    {
        $styles = [
            ['name' => 'Cinematic Realistic', 'extra_credit_cost' => 20],
            ['name' => '3D Animated Movie', 'extra_credit_cost' => 30],
            ['name' => 'Indian Mythology Epic', 'extra_credit_cost' => 35],
            ['name' => 'Storybook Illustration', 'extra_credit_cost' => 10],
            ['name' => 'Dark Horror Cinematic', 'extra_credit_cost' => 25],
            ['name' => 'Minimal Motion Comic', 'extra_credit_cost' => 5],
            ['name' => 'Documentary Realistic', 'extra_credit_cost' => 15],
            ['name' => 'Anime Style', 'extra_credit_cost' => 20],
        ];

        foreach ($styles as $style) {
            VideoStyle::firstOrCreate(
                ['name' => $style['name']],
                ['extra_credit_cost' => $style['extra_credit_cost']]
            );
        }
    }
}
