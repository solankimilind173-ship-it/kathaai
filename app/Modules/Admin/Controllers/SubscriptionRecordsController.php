<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionRecordsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name,slug,price'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        $plans = \App\Models\Plan::orderBy('price')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/SubscriptionRecords/Index', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'filters' => [
                'status' => $request->status,
                'plan_id' => $request->plan_id,
            ],
        ]);
    }
}
