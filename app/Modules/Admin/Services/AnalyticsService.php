<?php

namespace App\Modules\Admin\Services;

use App\Models\CreditTransaction;
use App\Models\Episode;
use App\Models\Project;
use App\Models\Scene;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Global system analytics for admin dashboard and analytics page. Supports date-range filtering.
 */
class AnalyticsService
{
    public function resolveDateRange(?string $filter, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = Carbon::now()->endOfDay();
        if ($filter === 'today') {
            return [$now->copy()->startOfDay(), $now];
        }
        if ($filter === 'week') {
            return [$now->copy()->startOfWeek(), $now];
        }
        if ($filter === 'month') {
            return [$now->copy()->startOfMonth(), $now];
        }
        if ($filter === 'year') {
            return [$now->copy()->startOfYear(), $now];
        }
        if ($filter === 'custom' && $dateFrom && $dateTo) {
            return [Carbon::parse($dateFrom)->startOfDay(), Carbon::parse($dateTo)->endOfDay()];
        }
        return [null, null];
    }

    public function getDashboardMetrics(?Carbon $from, ?Carbon $to): array
    {
        $totals = ($from !== null && $to !== null)
            ? $this->getDashboardMetricsBatch($from, $to)
            : [
                'total_users' => User::nonAdmin()->count(),
                'total_projects' => Project::whereHas('user', fn ($q) => $q->nonAdmin())->count(),
                'total_episodes' => Episode::whereHas('project.user', fn ($q) => $q->nonAdmin())->count(),
                'total_scenes' => Scene::whereHas('episode.project.user', fn ($q) => $q->nonAdmin())->count(),
                'total_credits_used' => (int) abs(CreditTransaction::whereHas('user', fn ($q) => $q->nonAdmin())->where('amount', '<', 0)->sum('amount')),
            ];

        $activeSubscriptions = Subscription::whereHas('user', fn ($q) => $q->nonAdmin())->where('status', 'active')->count();
        if ($activeSubscriptions === 0) {
            $activeSubscriptions = User::nonAdmin()->whereNotNull('plan_id')->count();
        }

        $revenueQuery = Subscription::query()
            ->whereHas('user', fn ($q) => $q->nonAdmin())
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->selectRaw('COALESCE(SUM(plans.price), 0) as total');
        if ($from !== null) {
            $revenueQuery->where('subscriptions.created_at', '>=', $from);
        }
        if ($to !== null) {
            $revenueQuery->where('subscriptions.created_at', '<=', $to);
        }
        $totalRevenue = (float) $revenueQuery->value('total');

        $weekStart = Carbon::now()->startOfWeek();
        $thisWeekEngagements = Project::whereHas('user', fn ($q) => $q->nonAdmin())->where('created_at', '>=', $weekStart)->count()
            + Episode::whereHas('project.user', fn ($q) => $q->nonAdmin())->where('created_at', '>=', $weekStart)->count();

        $activeJobs = (int) DB::table('jobs')->count();
        $failedJobsQuery = DB::table('failed_jobs');
        if ($from !== null) {
            $failedJobsQuery->where('failed_at', '>=', $from);
        }
        if ($to !== null) {
            $failedJobsQuery->where('failed_at', '<=', $to);
        }
        $failedJobs = (int) $failedJobsQuery->count();

        return [
            'total_users' => $totals['total_users'],
            'active_subscriptions' => $activeSubscriptions,
            'total_projects' => $totals['total_projects'],
            'total_episodes' => $totals['total_episodes'],
            'total_scenes' => $totals['total_scenes'],
            'total_credits_used' => $totals['total_credits_used'],
            'total_revenue' => round($totalRevenue, 2),
            'this_week_engagements' => $thisWeekEngagements,
            'active_jobs' => $activeJobs,
            'failed_jobs' => $failedJobs,
        ];
    }

    /**
     * Single-query aggregation for dashboard counts in a date range (uses indexes).
     * Counts only non-admin users and their projects, episodes, scenes, and credits.
     */
    private function getDashboardMetricsBatch(Carbon $from, Carbon $to): array
    {
        $adminRoles = "'" . implode("','", User::ADMIN_ROLES) . "'";
        $row = DB::selectOne(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE role NOT IN ({$adminRoles}) AND created_at >= ? AND created_at <= ?) AS total_users,
                (SELECT COUNT(*) FROM projects p INNER JOIN users u ON p.user_id = u.id WHERE u.role NOT IN ({$adminRoles}) AND p.created_at >= ? AND p.created_at <= ?) AS total_projects,
                (SELECT COUNT(*) FROM episodes e INNER JOIN projects p ON e.project_id = p.id INNER JOIN users u ON p.user_id = u.id WHERE u.role NOT IN ({$adminRoles}) AND e.created_at >= ? AND e.created_at <= ?) AS total_episodes,
                (SELECT COUNT(*) FROM scenes s INNER JOIN episodes e ON s.episode_id = e.id INNER JOIN projects p ON e.project_id = p.id INNER JOIN users u ON p.user_id = u.id WHERE u.role NOT IN ({$adminRoles}) AND s.created_at >= ? AND s.created_at <= ?) AS total_scenes,
                (SELECT COALESCE(ABS(SUM(ct.amount)), 0) FROM credit_transactions ct INNER JOIN users u ON ct.user_id = u.id WHERE u.role NOT IN ({$adminRoles}) AND ct.amount < 0 AND ct.created_at >= ? AND ct.created_at <= ?) AS total_credits_used",
            [$from, $to, $from, $to, $from, $to, $from, $to, $from, $to]
        );

        return [
            'total_users' => (int) $row->total_users,
            'total_projects' => (int) $row->total_projects,
            'total_episodes' => (int) $row->total_episodes,
            'total_scenes' => (int) $row->total_scenes,
            'total_credits_used' => (int) $row->total_credits_used,
        ];
    }

    public function getChartData(?Carbon $from, ?Carbon $to): array
    {
        if ($from === null || $to === null) {
            $from = Carbon::now()->subYear();
            $to = Carbon::now();
        }
        $days = $from->diffInDays($to);
        $groupByDay = $days <= 31;
        $dateFormat = $groupByDay ? '%Y-%m-%d' : '%Y-%m';
        return [
            'revenue' => $this->chartRevenue($from, $to, $dateFormat, $groupByDay),
            'user_registrations' => $this->chartUserRegistrations($from, $to, $dateFormat, $groupByDay),
            'credits_usage' => $this->chartCreditsUsage($from, $to, $dateFormat, $groupByDay),
            'project_creation' => $this->chartProjectCreation($from, $to, $dateFormat, $groupByDay),
        ];
    }

    public function getAnalyticsPageData(?Carbon $from, ?Carbon $to): array
    {
        if ($from === null || $to === null) {
            $from = Carbon::now()->subYears(2);
            $to = Carbon::now();
        }
        $days = $from->diffInDays($to);
        $groupByDay = $days <= 31;
        $dateFormat = $groupByDay ? '%Y-%m-%d' : '%Y-%m';
        return [
            'revenue_over_time' => $this->chartRevenue($from, $to, $dateFormat, $groupByDay),
            'credits_consumption_per_feature' => $this->creditsConsumptionByFeature($from, $to),
            'most_used_voice_language' => $this->mostUsedVoiceLanguage($from, $to),
            'most_used_video_style' => $this->mostUsedVideoStyle($from, $to),
            'top_active_users' => $this->topActiveUsers($from, $to, 10),
            'plan_conversion_stats' => $this->planConversionStats($from, $to),
        ];
    }

    private function chartRevenue(Carbon $from, Carbon $to, string $dateFormat, bool $groupByDay): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'mysql'
            ? "DATE_FORMAT(subscriptions.created_at, '{$dateFormat}')"
            : "strftime('{$dateFormat}', subscriptions.created_at)";
        $rows = Subscription::query()
            ->whereHas('user', fn ($q) => $q->nonAdmin())
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->whereBetween('subscriptions.created_at', [$from, $to])
            ->selectRaw("{$dateExpr} as period, COALESCE(SUM(plans.price), 0) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        return $rows->map(fn ($r) => ['period' => $r->period, 'total' => round((float) $r->total, 2)])->values()->all();
    }

    private function chartUserRegistrations(Carbon $from, Carbon $to, string $dateFormat, bool $groupByDay): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'mysql'
            ? "DATE_FORMAT(users.created_at, '{$dateFormat}')"
            : "strftime('{$dateFormat}', users.created_at)";
        $rows = User::query()
            ->nonAdmin()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("{$dateExpr} as period, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        return $rows->map(fn ($r) => ['period' => $r->period, 'count' => (int) $r->count])->values()->all();
    }

    private function chartCreditsUsage(Carbon $from, Carbon $to, string $dateFormat, bool $groupByDay): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'mysql'
            ? "DATE_FORMAT(credit_transactions.created_at, '{$dateFormat}')"
            : "strftime('{$dateFormat}', credit_transactions.created_at)";
        $rows = CreditTransaction::query()
            ->whereHas('user', fn ($q) => $q->nonAdmin())
            ->where('amount', '<', 0)
            ->whereBetween('credit_transactions.created_at', [$from, $to])
            ->selectRaw("{$dateExpr} as period, COALESCE(SUM(ABS(credit_transactions.amount)), 0) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        return $rows->map(fn ($r) => ['period' => $r->period, 'total' => (int) $r->total])->values()->all();
    }

    private function chartProjectCreation(Carbon $from, Carbon $to, string $dateFormat, bool $groupByDay): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'mysql'
            ? "DATE_FORMAT(projects.created_at, '{$dateFormat}')"
            : "strftime('{$dateFormat}', projects.created_at)";
        $rows = Project::query()
            ->whereHas('user', fn ($q) => $q->nonAdmin())
            ->whereBetween('projects.created_at', [$from, $to])
            ->selectRaw("{$dateExpr} as period, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        return $rows->map(fn ($r) => ['period' => $r->period, 'count' => (int) $r->count])->values()->all();
    }

    private function creditsConsumptionByFeature(?Carbon $from, ?Carbon $to): array
    {
        $query = CreditTransaction::query()
            ->where('amount', '<', 0)
            ->selectRaw('COALESCE(NULLIF(TRIM(feature), ""), "other") as feature_key, SUM(ABS(amount)) as total')
            ->groupBy(DB::raw('COALESCE(NULLIF(TRIM(feature), ""), "other")'));
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }
        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }
        return $query->orderByDesc('total')->get()->map(fn ($r) => [
            'feature' => $r->feature_key === 'other' ? 'Other' : $r->feature_key,
            'total' => (int) $r->total,
        ])->values()->all();
    }

    private function mostUsedVoiceLanguage(?Carbon $from, ?Carbon $to): array
    {
        $query = DB::table('project_dub_languages')
            ->join('projects', 'projects.id', '=', 'project_dub_languages.project_id')
            ->join('languages', 'languages.id', '=', 'project_dub_languages.language_id')
            ->selectRaw('languages.name as language_name, COUNT(project_dub_languages.id) as count')
            ->groupBy('languages.id', 'languages.name');
        if ($from !== null) {
            $query->where('projects.created_at', '>=', $from);
        }
        if ($to !== null) {
            $query->where('projects.created_at', '<=', $to);
        }
        return $query->orderByDesc('count')->limit(15)->get()->map(fn ($r) => [
            'language' => $r->language_name,
            'count' => (int) $r->count,
        ])->values()->all();
    }

    private function mostUsedVideoStyle(?Carbon $from, ?Carbon $to): array
    {
        $query = Project::query()
            ->selectRaw('COALESCE(quality, "default") as quality_key, COUNT(*) as count')
            ->groupBy(DB::raw('COALESCE(quality, "default")'));
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }
        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }
        return $query->orderByDesc('count')->get()->map(fn ($r) => [
            'style' => $r->quality_key === 'default' ? 'Not set' : $r->quality_key,
            'count' => (int) $r->count,
        ])->values()->all();
    }

    private function topActiveUsers(?Carbon $from, ?Carbon $to, int $limit = 10): array
    {
        $projectCounts = Project::query()
            ->whereNotNull('user_id')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
        $episodeCounts = Episode::query()
            ->join('projects', 'projects.id', '=', 'episodes.project_id')
            ->when($from, fn ($q) => $q->where('episodes.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('episodes.created_at', '<=', $to))
            ->selectRaw('projects.user_id, COUNT(episodes.id) as cnt')
            ->groupBy('projects.user_id')
            ->get()
            ->keyBy('user_id');
        $userIds = $projectCounts->keys()->merge($episodeCounts->keys())->unique();
        $activity = $userIds->mapWithKeys(fn ($id) => [
            $id => (int) ($projectCounts->get($id)?->cnt ?? 0) + (int) ($episodeCounts->get($id)?->cnt ?? 0),
        ])->sortDesc()->take($limit);
        if ($activity->isEmpty()) {
            return [];
        }
        $users = User::whereIn('id', $activity->keys())->get()->keyBy('id');
        return $activity->map(fn ($count, $id) => [
            'user_id' => $id,
            'name' => $users->get($id)?->name ?? '—',
            'email' => $users->get($id)?->email ?? '—',
            'activity' => $count,
        ])->values()->all();
    }

    private function planConversionStats(?Carbon $from, ?Carbon $to): array
    {
        $subscriptionsQuery = Subscription::query()
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->selectRaw('plans.name as plan_name, COUNT(subscriptions.id) as count')
            ->groupBy('plans.id', 'plans.name');
        if ($from !== null) {
            $subscriptionsQuery->where('subscriptions.created_at', '>=', $from);
        }
        if ($to !== null) {
            $subscriptionsQuery->where('subscriptions.created_at', '<=', $to);
        }
        $perPlan = $subscriptionsQuery->orderByDesc('count')->get()->map(fn ($r) => [
            'plan_name' => $r->plan_name,
            'count' => (int) $r->count,
        ])->values()->all();
        $totalNewSubscriptions = array_sum(array_column($perPlan, 'count'));
        $usersWithPlanQuery = User::query()->whereNotNull('plan_id');
        if ($from !== null) {
            $usersWithPlanQuery->where('updated_at', '>=', $from)->where('updated_at', '<=', $to);
        }
        $usersWithPlan = $usersWithPlanQuery->count();
        return [
            'new_subscriptions_per_plan' => $perPlan,
            'total_new_subscriptions' => $totalNewSubscriptions,
            'users_with_plan_in_period' => $usersWithPlan,
        ];
    }
}
