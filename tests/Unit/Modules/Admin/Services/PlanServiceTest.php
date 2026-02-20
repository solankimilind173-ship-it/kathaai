<?php

namespace Tests\Unit\Modules\Admin\Services;

use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use App\Modules\Admin\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlanService;
    }

    public function test_can_delete_returns_true_when_no_users_on_plan(): void
    {
        $plan = Plan::create([
            'name' => 'Orphan',
            'slug' => 'orphan',
            'price' => 5,
            'is_active' => true,
        ]);
        $this->assertTrue($this->service->canDelete($plan));
    }

    public function test_can_delete_returns_false_when_users_assigned(): void
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'is_active' => true,
        ]);
        User::factory()->create(['plan_id' => $plan->id]);
        $this->assertFalse($this->service->canDelete($plan));
    }

    public function test_sync_plan_features_creates_and_updates_from_array(): void
    {
        $def1 = FeatureDefinition::create(['key' => 'max_projects', 'label' => 'Max Projects', 'type' => 'integer']);
        $def2 = FeatureDefinition::create(['key' => 'allow_4k', 'label' => '4K', 'type' => 'boolean']);
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
        ]);
        $features = [
            ['key' => 'max_projects', 'value' => '10'],
            ['key' => 'allow_4k', 'value' => '1'],
        ];
        $this->service->syncPlanFeatures($plan, $features);
        $plan->refresh();
        $this->assertSame(10, $plan->getFeatureValue('max_projects'));
        $this->assertTrue($plan->getFeatureValue('allow_4k'));
    }

    public function test_sync_plan_features_removes_features_not_in_payload(): void
    {
        $def = FeatureDefinition::create(['key' => 'max_projects', 'label' => 'Max Projects', 'type' => 'integer']);
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 19.99,
            'is_active' => true,
        ]);
        PlanFeature::create([
            'plan_id' => $plan->id,
            'feature_definition_id' => $def->id,
            'value' => '5',
        ]);
        $this->service->syncPlanFeatures($plan, []);
        $this->assertSame(0, $plan->planFeatures()->count());
    }
}
