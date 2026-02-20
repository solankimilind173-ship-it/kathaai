<?php

namespace Tests\Unit\Services;

use App\Services\CreditCalculator;
use Tests\TestCase;

class CreditCalculatorTest extends TestCase
{
    private CreditCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CreditCalculator;
    }

    public function test_calculate_basic_video_cost(): void
    {
        $options = [
            'minutes' => 5,
            'quality' => '1080p',
            'dub_languages' => [],
        ];
        $cost = $this->calculator->calculate($options);
        $this->assertSame(5 * config('ai_costs.video_per_minute'), $cost);
    }

    public function test_calculate_includes_4k_multiplier(): void
    {
        $base = ['minutes' => 2, 'quality' => '1080p', 'dub_languages' => []];
        $cost1080 = $this->calculator->calculate($base);

        $base4k = ['minutes' => 2, 'quality' => '4k', 'dub_languages' => []];
        $cost4k = $this->calculator->calculate($base4k);

        $expected4k = (int) ($cost1080 * config('ai_costs.4k_multiplier'));
        $this->assertSame($expected4k, $cost4k);
    }

    public function test_calculate_includes_dubbing_per_language(): void
    {
        $options = [
            'minutes' => 1,
            'quality' => '1080p',
            'dub_languages' => ['en', 'hi'],
        ];
        $cost = $this->calculator->calculate($options);
        $base = 1 * config('ai_costs.video_per_minute');
        $dub = 2 * config('ai_costs.dubbing_per_language');
        $this->assertSame($base + $dub, $cost);
    }

    public function test_calculate_includes_intro_song_and_background_music(): void
    {
        $options = [
            'minutes' => 1,
            'quality' => '1080p',
            'dub_languages' => [],
            'intro_song' => true,
            'background_music' => true,
        ];
        $cost = $this->calculator->calculate($options);
        $expected = config('ai_costs.video_per_minute')
            + config('ai_costs.intro_song_generation')
            + config('ai_costs.background_music');
        $this->assertSame($expected, $cost);
    }

    public function test_calculate_includes_reels(): void
    {
        $options = [
            'minutes' => 1,
            'quality' => '1080p',
            'reels' => 3,
        ];
        $cost = $this->calculator->calculate($options);
        $expected = config('ai_costs.video_per_minute') + 3 * config('ai_costs.reel_generation');
        $this->assertSame($expected, $cost);
    }

    public function test_calculate_render_cost_basic(): void
    {
        $options = ['duration_minutes' => 2];
        $cost = $this->calculator->calculateRenderCost($options);
        $expected = (int) ceil(2 * config('ai_costs.render_per_minute', 25));
        $this->assertSame($expected, $cost);
    }

    public function test_calculate_render_cost_4k(): void
    {
        $options = ['duration_minutes' => 1, 'resolution' => '4k'];
        $cost = $this->calculator->calculateRenderCost($options);
        $base = (int) ceil(1 * config('ai_costs.render_per_minute', 25));
        $expected = (int) ceil($base * config('ai_costs.4k_multiplier', 2));
        $this->assertSame($expected, $cost);
    }

    public function test_calculate_render_cost_with_background_music(): void
    {
        $options = ['duration_minutes' => 1, 'background_music' => true];
        $cost = $this->calculator->calculateRenderCost($options);
        $base = (int) ceil(1 * config('ai_costs.render_per_minute', 25));
        $this->assertSame($base + (int) config('ai_costs.render_background_music', 5), $cost);
    }

    public function test_calculate_render_cost_returns_non_negative(): void
    {
        $options = ['duration_minutes' => 0];
        $cost = $this->calculator->calculateRenderCost($options);
        $this->assertGreaterThanOrEqual(0, $cost);
    }
}
