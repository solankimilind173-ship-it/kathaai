<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Modules\Admin\Requests\StorePlanRequest;
use App\Modules\Admin\Requests\UpdatePlanRequest;
use App\Modules\Admin\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsController extends Controller
{
    public function __construct(private PlanService $planService) {}

    public function index(Request $request): Response
    {
        $query = Plan::query()->orderBy('price');
        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        }
        $plans = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Subscriptions/Index', [
            'plans' => $plans,
            'filters' => ['search' => $search->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Subscriptions/Create', [
            'featureDefinitions' => FeatureDefinition::orderBy('sort_order')->get(),
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $features = $data['features'] ?? [];
        unset($data['features']);
        $data['credit_rollover'] = $request->boolean('credit_rollover');
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($data, $features) {
            $plan = Plan::create($data);
            $this->planService->syncPlanFeatures($plan, $features);
        });

        return redirect()->route('admin.subscriptions.index')->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan): Response
    {
        $plan->load('planFeatures.featureDefinition');
        $featureDefinitions = FeatureDefinition::orderBy('sort_order')->get();
        $featureValues = $plan->planFeatures->keyBy(fn ($pf) => $pf->featureDefinition->key)->map(fn ($pf) => $pf->value)->all();
        foreach ($featureDefinitions as $def) {
            if (! array_key_exists($def->key, $featureValues)) {
                $v = $plan->getAttribute($def->key);
                $featureValues[$def->key] = $v === null ? '' : (is_bool($v) ? ($v ? '1' : '0') : (string) $v);
            }
        }

        return Inertia::render('Admin/Subscriptions/Edit', [
            'plan' => $plan,
            'featureDefinitions' => $featureDefinitions,
            'featureValues' => $featureValues,
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $data = $request->validated();
        $features = $data['features'] ?? [];
        unset($data['features']);
        $data['credit_rollover'] = $request->boolean('credit_rollover');
        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($plan, $data, $features) {
            $plan->update($data);
            $this->planService->syncPlanFeatures($plan, $features);
        });

        return redirect()->route('admin.subscriptions.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if (! $this->planService->canDelete($plan)) {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Cannot delete this plan because one or more users are assigned to it. Reassign or remove them first.');
        }

        $plan->delete();

        return redirect()->route('admin.subscriptions.index')->with('success', 'Plan deleted successfully.');
    }

    public function toggle(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return redirect()->route('admin.subscriptions.index')->with('success', $plan->is_active ? 'Plan activated.' : 'Plan deactivated.');
    }
}
