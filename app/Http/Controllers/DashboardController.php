<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Plan;
use App\Models\Project;
use App\Models\RenderLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        $imagesCount = Character::whereHas('project', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('image_path')
            ->count();

        $projectsCreated = Project::where('user_id', $userId)->count();
        $projectsCompleted = Project::where('user_id', $userId)->where('status', 'completed')->count();
        $videosGenerated = RenderLog::whereHas('project', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'completed')
            ->whereNotNull('output_url')
            ->count();

        $creditsUsed = (int) $user->credits;
        $plan = $user->plan;
        $creditsAllowance = $plan?->monthly_credits ?? 0;
        $hasSubscription = $user->plan_id !== null;

        $latestProjects = Project::where('user_id', $userId)
            ->withCount(['episodes', 'characters'])
            ->latest()
            ->take(5)
            ->get();

        $plans = Plan::orderBy('price')
            ->get()
            ->filter(fn ($p) => $p->is_active !== false);

        return Inertia::render('Dashboard', [
            'analytics' => [
                'images_added' => $imagesCount,
                'videos_generated' => $videosGenerated,
                'projects_created' => $projectsCreated,
                'projects_completed' => $projectsCompleted,
                'credits_used' => $creditsUsed,
                'credits_allowance' => $creditsAllowance,
            ],
            'latestProjects' => $latestProjects,
            'plans' => $plans,
            'hasSubscription' => $hasSubscription,
            'plan' => $plan,
        ]);
    }
}
