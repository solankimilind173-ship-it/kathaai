<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectEngagement;
use App\Models\ProjectShareToken;
use Inertia\Inertia;

class ShareController extends Controller
{
    /**
     * Public view of a project by share token. No auth required. View only.
     */
    public function show(string $token)
    {
        $shareToken = ProjectShareToken::where('token', $token)->with('project')->first();

        if (! $shareToken || ! $shareToken->project) {
            abort(404, 'Invalid or expired share link.');
        }

        $project = $shareToken->project;

        if (! $project->is_public) {
            abort(404, 'This project is no longer shared.');
        }

        ProjectEngagement::incrementFor($project, 'view_count');

        $project->loadCount(['episodes', 'characters', 'scenes']);
        $project->load([
            'episodes' => fn ($q) => $q->with(['scenes' => fn ($q) => $q->orderBy('scene_number')])->orderBy('episode_number'),
            'characters' => fn ($q) => $q->with('selectedImage'),
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'plan' => null,
            'projectAnalytics' => null,
            'estimatedCredits' => null,
            'creditOptions' => null,
            'subtitleStyles' => [],
            'statusLabel' => $project->status->label(),
            'sourceLabel' => $project->source_type->label(),
            'sceneRegenerationCosts' => [],
            'userCredits' => 0,
            'viewOnly' => true,
            'shareUrl' => null,
        ]);
    }
}
