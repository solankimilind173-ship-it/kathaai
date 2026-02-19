<?php

namespace App\Services;

class CreditCalculator
{
    public function calculate($options)
    {
        $cost = 0;

        $cost += $options['minutes'] * config('ai_costs.video_per_minute');

        if ($options['quality'] === '4k') {
            $cost *= config('ai_costs.4k_multiplier');
        }

        $cost += count($options['dub_languages'] ?? [])
            * config('ai_costs.dubbing_per_language');

        if (!empty($options['intro_song'])) {
            $cost += config('ai_costs.intro_song_generation');
        }

        if (!empty($options['background_music'])) {
            $cost += config('ai_costs.background_music');
        }

        $cost += ($options['reels'] ?? 0)
            * config('ai_costs.reel_generation');

        return $cost;
    }
}
