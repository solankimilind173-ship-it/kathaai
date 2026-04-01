<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UpgradePriceResolutionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_plan_returns_stripe_price_id_from_plan_column_for_monthly(): void
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
            'stripe_price_id' => 'price_plan_monthly_123',
            'stripe_yearly_price_id' => 'price_plan_yearly_456',
        ]);
        $this->assertSame('price_plan_monthly_123', $plan->getStripePriceIdForInterval('monthly'));
    }

    public function test_plan_returns_stripe_yearly_price_id_from_plan_column_for_yearly(): void
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
            'stripe_price_id' => 'price_monthly',
            'stripe_yearly_price_id' => 'price_yearly_999',
        ]);
        $this->assertSame('price_yearly_999', $plan->getStripePriceIdForInterval('yearly'));
    }

    public function test_plan_falls_back_to_config_when_plan_column_null(): void
    {
        Config::set('services.stripe.plans.starter.monthly', 'price_config_monthly');
        Config::set('services.stripe.plans.starter.yearly', 'price_config_yearly');
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'is_active' => true,
            'stripe_price_id' => null,
            'stripe_yearly_price_id' => null,
        ]);
        $this->assertSame('price_config_monthly', $plan->getStripePriceIdForInterval('monthly'));
        $this->assertSame('price_config_yearly', $plan->getStripePriceIdForInterval('yearly'));
    }

    public function test_plan_returns_null_when_no_plan_or_config_value(): void
    {
        $plan = Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'is_active' => true,
        ]);
        Config::set('services.stripe.plans.free.monthly', null);
        Config::set('services.stripe.plans.free.yearly', null);
        $this->assertNull($plan->getStripePriceIdForInterval('monthly'));
        $this->assertNull($plan->getStripePriceIdForInterval('yearly'));
    }
}
