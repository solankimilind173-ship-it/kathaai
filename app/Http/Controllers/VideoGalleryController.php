<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VideoGalleryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $projects = Project::where('user_id', $userId)
            ->with(['episodes' => fn ($q) => $q->orderBy('id')])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function (Project $project) {
                $episodes = $project->episodes->map(fn ($ep) => [
                    'id' => $ep->id,
                    'title' => $ep->title,
                    'summary' => $ep->summary,
                    'status' => $ep->status,
                    'video_url' => null, // placeholder until video generation is implemented
                ])->values()->all();

                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'episodes' => $episodes,
                ];
            })
            ->filter(fn ($p) => count($p['episodes']) > 0)
            ->values()
            ->all();

        return Inertia::render('Gallery/VideoIndex', [
            'projects' => $projects,
        ]);
    }
}
