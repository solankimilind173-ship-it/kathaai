<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Modules\Admin\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const DASHBOARD_CACHE_TTL_SECONDS = 120;

    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request): Response
    {
        [$from, $to] = $this->analytics->resolveDateRange(
            $request->query('filter'),
            $request->query('date_from'),
            $request->query('date_to')
        );

        $cacheKey = 'admin.dashboard.'.($request->query('filter') ?? 'all').'.'.($from?->toDateTimeString() ?? 'null').'.'.($to?->toDateTimeString() ?? 'null');

        $metrics = Cache::remember($cacheKey.'.metrics', self::DASHBOARD_CACHE_TTL_SECONDS, fn () => $this->analytics->getDashboardMetrics($from, $to));
        $chartData = Cache::remember($cacheKey.'.charts', self::DASHBOARD_CACHE_TTL_SECONDS, fn () => $this->analytics->getChartData($from, $to));

        $recentUsers = User::nonAdmin()->with('plan')->latest()->take(5)->get();
        $recentProjects = Project::whereHas('user', fn ($q) => $q->nonAdmin())->with(['user', 'user.plan'])->latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $metrics,
            'chartData' => $chartData,
            'filter' => $request->query('filter'),
            'dateFrom' => $from?->toDateString(),
            'dateTo' => $to?->toDateString(),
            'recentUsers' => $recentUsers,
            'recentProjects' => $recentProjects,
        ]);
    }
}
