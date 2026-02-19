<?php

namespace App\Modules\Admin\Services;

use App\Models\Episode;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Aggregates user activity and credit data for admin user detail and charts.
 */
class UserAnalyticsService
{
    public function getTotals(User $user): array
    {
        $projectsCount = $user->projects()->count();
        $episodesCount = $user->projects()->withCount('episodes')->get()->sum('episodes_count');
        $scenesCount = Scene::whereHas('episode.project', fn ($q) => $q->where('user_id', $user->id))->count();

        $creditsGranted = (int) $user->creditTransactions()->where('amount', '>', 0)->sum('amount');
        $creditsUsed = (int) abs((float) $user->creditTransactions()->where('amount', '<', 0)->sum('amount'));
        $remainingCredits = (int) $user->credits;

        return [
            'total_projects' => $projectsCount,
            'total_episodes' => $episodesCount,
            'total_scenes' => $scenesCount,
            'total_videos_rendered' => $episodesCount,
            'credits_assigned' => $creditsGranted,
            'credits_used' => $creditsUsed,
            'credits_remaining' => $remainingCredits,
        ];
    }

    public function creditsUsageOverTime(User $user, int $months = 6): array
    {
        $since = Carbon::now()->subMonths($months);
        $rows = $user->creditTransactions()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as used, SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as granted")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => [
            'month' => $r->month,
            'used' => (int) $r->used,
            'granted' => (int) $r->granted,
        ])->values()->all();
    }

    public function projectsCreatedOverTime(User $user, int $months = 6): array
    {
        $since = Carbon::now()->subMonths($months);
        $rows = $user->projects()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => ['month' => $r->month, 'count' => (int) $r->count])->values()->all();
    }

    public function engagementPerWeek(User $user, int $weeks = 12): array
    {
        $since = Carbon::now()->subWeeks($weeks);
        $projects = $user->projects()->where('created_at', '>=', $since)->get();
        $byWeek = [];

        foreach ($projects as $p) {
            $w = $p->created_at->format('Y-W');
            if (! isset($byWeek[$w])) {
                $byWeek[$w] = ['week' => $w, 'projects' => 0, 'episodes' => 0];
            }
            $byWeek[$w]['projects']++;
        }

        $episodes = Episode::whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->where('created_at', '>=', $since)
            ->get();
        foreach ($episodes as $e) {
            $w = $e->created_at->format('Y-W');
            if (! isset($byWeek[$w])) {
                $byWeek[$w] = ['week' => $w, 'projects' => 0, 'episodes' => 0];
            }
            $byWeek[$w]['episodes']++;
        }
        ksort($byWeek);

        return array_values($byWeek);
    }

    public function videoRendersPerMonth(User $user, int $months = 6): array
    {
        $since = Carbon::now()->subMonths($months);
        $rows = Episode::whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => ['month' => $r->month, 'count' => (int) $r->count])->values()->all();
    }
}
