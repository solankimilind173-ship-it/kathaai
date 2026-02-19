<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateScenesJob;
use App\Models\Episode;
use App\Models\Project;
use App\Models\Scene;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EpisodeController extends Controller
{
    public function store(Request $request, Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $nextNumber = (int) Episode::where('project_id', $project->id)->max('episode_number') + 1;

        $episode = Episode::create([
            'project_id'        => $project->id,
            'title'             => $request->input('title') ?: "Episode {$nextNumber}",
            'episode_number'    => $nextNumber,
            'summary'           => null,
            'status'            => 'pending',
            'total_credits_used'=> 0,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Episode added.');
    }

    public function destroy(Episode $episode)
    {
        $episode->load('project');
        if ($episode->project->user_id !== auth()->id()) {
            abort(403);
        }

        $project = $episode->project;
        $episode->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Episode deleted.');
    }

    public function regenerate(Episode $episode)
    {
        $episode->load('project');
        if ($episode->project->user_id !== auth()->id()) {
            abort(403);
        }

        Scene::where('episode_id', $episode->id)->delete();

        $episode->update(['status' => 'generating_scenes']);

        GenerateScenesJob::dispatch($episode);

        return redirect()->route('projects.show', $episode->project)->with('success', 'Episode regeneration started.');
    }

    public function reorder(Request $request, Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

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
