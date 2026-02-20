<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRenderSettings;
use App\Models\SceneRenderSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TimelineController extends Controller
{
    /**
     * Timeline UI: flat list of scenes with render settings for drag/drop, trim, transition, etc.
     */
    public function show(Project $project)
    {
        $project->load(['episodes' => fn ($q) => $q->orderBy('episode_number')]);
        $project->episodes->load(['scenes' => fn ($q) => $q->orderBy('scene_number')]);

        $scenes = $project->scenes->map(fn ($s) => [
            'id' => $s->id,
            'episode_id' => $s->episode_id,
            'episode_number' => $s->episode?->episode_number,
            'scene_number' => $s->scene_number,
            'title' => $s->title,
            'description' => $s->description,
            'duration' => $s->duration,
            'image_url' => $s->image_url,
        ])->values()->all();

        $renderSettings = $project->renderSettings;
        $sceneSettings = $project->sceneRenderSettings->keyBy('scene_id');

        $savedOrder = $project->sceneRenderSettings()
            ->orderBy('sort_order')
            ->pluck('scene_id')
            ->all();

        $allSceneIds = $project->scenes->sortBy([
            fn ($a, $b) => ($a->episode->episode_number ?? 0) <=> ($b->episode->episode_number ?? 0),
            fn ($a, $b) => $a->scene_number <=> $b->scene_number,
        ])->pluck('id')->all();

        $orderedSceneIds = empty($savedOrder)
            ? $allSceneIds
            : array_values(array_unique(array_merge($savedOrder, array_diff($allSceneIds, $savedOrder))));

        $timelineScenes = collect($orderedSceneIds)->values()->map(function ($sceneId, $index) use ($scenes, $sceneSettings) {
            $scene = collect($scenes)->firstWhere('id', $sceneId);
            if (! $scene) {
                return null;
            }
            $settings = $sceneSettings->get($sceneId);
            return [
                ...$scene,
                'sort_order' => $index,
                'duration_trimmed' => $settings?->duration_trimmed,
                'transition_style' => $settings?->transition_style,
            ];
        })->filter()->values()->all();

        return Inertia::render('Projects/Timeline', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
            ],
            'scenes' => $timelineScenes,
            'renderSettings' => $renderSettings ? [
                'background_music_url' => $renderSettings->background_music_url,
                'subtitles_enabled' => $renderSettings->subtitles_enabled,
                'subtitle_style' => $renderSettings->subtitle_style,
            ] : [
                'background_music_url' => null,
                'subtitles_enabled' => true,
                'subtitle_style' => 'default',
            ],
            'transitionStyles' => [
                ['value' => 'none', 'label' => 'None'],
                ['value' => 'fade', 'label' => 'Fade'],
                ['value' => 'dissolve', 'label' => 'Dissolve'],
                ['value' => 'wipe', 'label' => 'Wipe'],
                ['value' => 'slide', 'label' => 'Slide'],
            ],
            'subtitleStyles' => [
                ['value' => 'default', 'label' => 'Default'],
                ['value' => 'minimal', 'label' => 'Minimal'],
                ['value' => 'bold', 'label' => 'Bold'],
                ['value' => 'outline', 'label' => 'Outline'],
            ],
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validEpisodeIds = $project->episodes()->pluck('id')->all();

        $request->validate([
            'scene_order' => 'required|array',
            'scene_order.*' => [Rule::exists('scenes', 'id')->whereIn('episode_id', $validEpisodeIds)],
            'scenes' => 'nullable|array',
            'scenes.*.id' => ['required', Rule::exists('scenes', 'id')->whereIn('episode_id', $validEpisodeIds)],
            'scenes.*.duration_trimmed' => 'nullable|integer|min:0',
            'scenes.*.transition_style' => 'nullable|string|max:64',
            'background_music_url' => 'nullable|string|max:500',
            'subtitles_enabled' => 'nullable|boolean',
            'subtitle_style' => 'nullable|string|max:64',
        ]);

        $sceneOrder = $request->scene_order;
        $scenesPayload = collect($request->input('scenes', []))->keyBy('id');

        ProjectRenderSettings::updateOrCreate(
            ['project_id' => $project->id],
            [
                'background_music_url' => $request->input('background_music_url'),
                'subtitles_enabled' => (bool) $request->input('subtitles_enabled', true),
                'subtitle_style' => $request->input('subtitle_style', 'default'),
            ]
        );

        foreach ($sceneOrder as $index => $sceneId) {
            $payload = $scenesPayload->get($sceneId, []);
            SceneRenderSettings::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'scene_id' => $sceneId,
                ],
                [
                    'sort_order' => $index,
                    'duration_trimmed' => $payload['duration_trimmed'] ?? null,
                    'transition_style' => $payload['transition_style'] ?? null,
                ]
            );
        }

        return redirect()->route('timeline.show', $project)->with('success', 'Timeline settings saved.');
    }
}
