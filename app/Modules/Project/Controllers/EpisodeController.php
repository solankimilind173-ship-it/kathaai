<?php

namespace App\Modules\Project\Controllers;

use App\Jobs\GenerateScenesJob;
use App\Models\Episode;
use App\Models\Project;
use App\Models\Scene;
use App\Services\RegenerationLimitService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class EpisodeController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $request->validate(['title' => 'nullable|string|max:255']);

        $nextNumber = (int) Episode::where('project_id', $project->id)->max('episode_number') + 1;
        Episode::create([
            'project_id' => $project->id,
            'title' => $request->input('title') ?: "Episode {$nextNumber}",
            'episode_number' => $nextNumber,
            'summary' => null,
            'status' => 'pending',
            'total_credits_used' => 0,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Episode added.');
    }

    public function destroy(Episode $episode)
    {
        $episode->load('project');
        if ($episode->project->user_id !== auth()->id()) {
            abort(404);
        }
        $project = $episode->project;
        $episode->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Episode deleted.');
    }

    public function regenerate(Episode $episode, RegenerationLimitService $regenerationLimit)
    {
        $episode->load('project');
        if ($episode->project->user_id !== auth()->id()) {
            abort(404);
        }
        if ($episode->project->is_archived) {
            abort(403, 'AI actions are not allowed on archived projects. Restore the project first.');
        }
        if (! $regenerationLimit->canRegenerateEpisode($episode)) {
            return redirect()->route('projects.show', $episode->project)
                ->withErrors(['regeneration' => $regenerationLimit->limitReachedMessage('episode')]);
        }
        $regenerationLimit->recordEpisodeRegeneration($episode);
        Scene::where('episode_id', $episode->id)->delete();
        $episode->update(['status' => 'generating_scenes']);
        GenerateScenesJob::dispatch($episode);

        return redirect()->route('projects.show', $episode->project)->with('success', 'Episode regeneration started.');
    }

    public function retryScenes(Episode $episode)
    {
        $episode->load('project');
        if ($episode->project->user_id !== auth()->id()) {
            abort(404);
        }
        if ($episode->project->is_archived) {
            return redirect()->route('projects.show', $episode->project)->withErrors(['episode' => 'Cannot retry on an archived project.']);
        }
        if (($episode->status ?? '') !== 'scene_failed') {
            return redirect()->route('projects.show', $episode->project)->withErrors(['episode' => 'Can only retry when episode scene generation has failed.']);
        }
        $episode->update(['status' => 'generating_scenes']);
        GenerateScenesJob::dispatch($episode);

        return redirect()->route('projects.show', $episode->project)->with('success', 'Scene generation has been queued for retry.');
    }

    public function reorder(Request $request, Project $project)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => [Rule::exists('episodes', 'id')->where('project_id', $project->id)],
        ]);
        foreach ($request->order as $position => $episodeId) {
            Episode::where('id', $episodeId)->where('project_id', $project->id)->update([
                'episode_number' => $position + 1,
            ]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Episodes reordered.');
    }
}
