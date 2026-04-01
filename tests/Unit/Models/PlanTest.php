<?php

namespace Tests\Unit\Models;

use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use DatabaseMigrations;

    public function test_get_feature_value_returns_plan_attribute_when_no_plan_feature(): void
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'max_projects' => 5,
            'is_active' => true,
        ]);
        $this->assertSame(5, $plan->getFeatureValue('max_projects'));
    }

    public function test_get_feature_value_returns_null_for_unknown_key(): void
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'is_active' => true,
        ]);
        $this->assertNull($plan->getFeatureValue('unknown_feature'));
    }

    public function test_cast_is_active_and_credit_rollover_as_boolean(): void
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => 1,
            'credit_rollover' => 1,
        ]);
        $this->assertTrue($plan->is_active);
        $this->assertTrue($plan->credit_rollover);
    }

    public function test_get_feature_value_returns_plan_feature_value_from_feature_definition(): void
    {
        $def = FeatureDefinition::create([
            'key' => 'max_episodes_per_project',
            'label' => 'Max Episodes',
            'type' => 'integer',
        ]);
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
        ]);
        PlanFeature::create([
            'plan_id' => $plan->id,
            'feature_definition_id' => $def->id,
            'value' => '20',
        ]);
        $this->assertSame(20, $plan->getFeatureValue('max_episodes_per_project'));
    }

    public function test_get_feature_value_casts_boolean_from_plan_feature(): void
    {
        $def = FeatureDefinition::create([
            'key' => 'allow_4k',
            'label' => 'Allow 4K',
            'type' => 'boolean',
        ]);
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
        ]);
        PlanFeature::create([
            'plan_id' => $plan->id,
            'feature_definition_id' => $def->id,
            'value' => '1',
        ]);
        $this->assertTrue($plan->getFeatureValue('allow_4k'));
    }

    public function test_get_attribute_returns_feature_value_via_get_feature_value(): void
    {
        $def = FeatureDefinition::create([
            'key' => 'max_projects',
            'label' => 'Max Projects',
            'type' => 'integer',
        ]);
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'is_active' => true,
        ]);
        PlanFeature::create([
            'plan_id' => $plan->id,
            'feature_definition_id' => $def->id,
            'value' => '3',
        ]);
        $this->assertSame(3, $plan->max_projects);
    }

    public function test_get_feature_value_returns_trailer_generation_flag(): void
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
            'allow_trailer_generation' => true,
        ]);

        $this->assertTrue($plan->getFeatureValue('allow_trailer_generation'));
        $this->assertTrue($plan->allow_trailer_generation);
    }
}
