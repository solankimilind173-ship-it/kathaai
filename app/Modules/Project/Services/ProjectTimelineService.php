<?php

namespace App\Modules\Project\Services;

use App\Models\Project;

class ProjectTimelineService
{
    /**
     * Build a chronological timeline of project activity for display.
     *
     * @return array<int, array{date: ?string, label: string, description: ?string}>
     */
    public function buildTimeline(Project $project): array
    {
        $items = [];

        $items[] = [
            'date' => $project->created_at?->toIso8601String(),
            'label' => 'Project created',
            'description' => null,
        ];

        foreach ($project->episodes ?? [] as $episode) {
            if ($episode->created_at) {
                $items[] = [
                    'date' => $episode->created_at->toIso8601String(),
                    'label' => 'Episode added',
                    'description' => $episode->title ?: "Episode {$episode->id}",
                ];
            }
        }

        $lastLog = $project->renderLogs->first();
        if ($lastLog && $lastLog->created_at) {
            $items[] = [
                'date' => $lastLog->created_at->toIso8601String(),
                'label' => 'Last render activity',
                'description' => $lastLog->message ?? null,
            ];
        }

        usort($items, fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return array_slice($items, 0, 20);
    }
}
