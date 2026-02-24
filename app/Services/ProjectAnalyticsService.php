<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;

class ProjectAnalyticsService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    private const CACHE_KEY_PREFIX = 'project_analytics:';

    /** action_type => display label for credits breakdown. */
    private const ACTION_LABELS = [
        'scene_generation' => 'Scene text',
        'scene_image_regeneration' => 'Image generation',
        'scene_voice_regeneration' => 'Voice generation',
        'project_render' => 'Rendering',
    ];

    /**
     * Get analytics for the project: total credits, credits per feature, time taken, total scenes, total duration.
     * Cached for 5 minutes; invalidate when credits are used (see CreditService).
     */
    public function getAnalytics(Project $project): array
    {
        $key = self::CACHE_KEY_PREFIX . $project->id;

        return Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($project) {
            return $this->computeAnalytics($project);
        });
    }

    /**
     * Invalidate cached analytics for a project (call when credits are used).
     */
    public static function invalidateCache(Project $project): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $project->id);
    }

    private function computeAnalytics(Project $project): array
    {
        $totalCreditsUsed = (int) ($project->total_credits_used ?? 0);

        // Aggregate in DB: group by action_type or feature, sum credits or ABS(amount)
        $creditsByFeature = CreditTransaction::query()
            ->where('project_id', $project->id)
            ->where('type', 'usage')
            ->where(function ($q) {
                $q->where('amount', '<', 0)->orWhereNotNull('credits');
            })
            ->selectRaw("COALESCE(action_type, feature, 'usage') as feature_key, SUM(COALESCE(credits, ABS(amount))) as total")
            ->groupByRaw("COALESCE(action_type, feature, 'usage')")
            ->pluck('total', 'feature_key')
            ->map(fn ($total) => (int) $total)
            ->all();

        $creditsPerFeature = [];
        foreach (self::ACTION_LABELS as $key => $label) {
            $creditsPerFeature[] = [
                'label' => $label,
                'feature' => $key,
                'credits' => (int) ($creditsByFeature[$key] ?? 0),
            ];
        }
        foreach (array_keys($creditsByFeature) as $key) {
            if (! array_key_exists($key, self::ACTION_LABELS)) {
                $creditsPerFeature[] = [
                    'label' => $this->humanFeatureName($key),
                    'feature' => $key,
                    'credits' => (int) $creditsByFeature[$key],
                ];
            }
        }

        $timeTaken = $this->computeTimeTaken($project);
        $totalScenes = (int) $project->scenes()->count();
        $totalDurationSeconds = $this->computeTotalDurationSeconds($project);

        return [
            'total_credits_used' => $totalCreditsUsed,
            'credits_per_feature' => array_values($creditsPerFeature),
            'time_taken_seconds' => $timeTaken,
            'time_taken_human' => $this->formatDuration($timeTaken),
            'total_scenes' => $totalScenes,
            'total_duration_seconds' => $totalDurationSeconds,
            'total_duration_human' => $this->formatDuration($totalDurationSeconds),
        ];
    }

    private function computeTimeTaken(Project $project): int
    {
        $start = $project->created_at?->timestamp;
        if (! $start) {
            return 0;
        }

        $lastTransaction = CreditTransaction::query()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->value('created_at');

        $lastSceneUpdated = $project->scenes()->orderByDesc('scenes.updated_at')->value('scenes.updated_at');

        $endTs = $start;
        if ($lastTransaction) {
            $ts = $lastTransaction instanceof \DateTimeInterface ? $lastTransaction->getTimestamp() : strtotime($lastTransaction);
            if ($ts > $endTs) {
                $endTs = $ts;
            }
        }
        if ($lastSceneUpdated) {
            $ts = $lastSceneUpdated instanceof \DateTimeInterface ? $lastSceneUpdated->getTimestamp() : strtotime($lastSceneUpdated);
            if ($ts > $endTs) {
                $endTs = $ts;
            }
        }

        return max(0, $endTs - $start);
    }

    private function computeTotalDurationSeconds(Project $project): int
    {
        $projectId = $project->id;
        $scenes = $project->scenes()->with(['sceneRenderSettings' => fn ($q) => $q->where('project_id', $projectId)])->get();

        $total = 0;
        foreach ($scenes as $scene) {
            $settings = $scene->sceneRenderSettings->first();
            $total += $settings?->duration_trimmed ?? $scene->duration ?? 0;
        }

        return (int) $total;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        if ($seconds < 3600) {
            $m = (int) floor($seconds / 60);
            $s = $seconds % 60;
            return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
        }
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        $parts = ["{$h}h"];
        if ($m > 0) {
            $parts[] = "{$m}m";
        }
        if ($s > 0) {
            $parts[] = "{$s}s";
        }
        return implode(' ', $parts);
    }

    private function humanFeatureName(string $key): string
    {
        return str_replace('_', ' ', ucfirst($key));
    }
}
