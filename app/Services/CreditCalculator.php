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

    /**
     * Calculate credit cost for a full project render based on settings.
     * Options: resolution (1080p|4k), format (16:9|9:16), fps (24|30), duration_minutes, background_music (bool).
     */
    public function calculateRenderCost(array $options): int
    {
        $minutes = (float) ($options['duration_minutes'] ?? 0);
        $cost = (int) ceil($minutes * config('ai_costs.render_per_minute', 25));

        if (($options['resolution'] ?? '1080p') === '4k') {
            $cost = (int) ceil($cost * config('ai_costs.4k_multiplier', 2));
        }

        if (! empty($options['background_music'])) {
            $cost += (int) config('ai_costs.render_background_music', 5);
        }

        return max(0, $cost);
    }
}
