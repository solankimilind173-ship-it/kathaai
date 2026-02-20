<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImageGalleryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $projects = Project::where('user_id', $userId)
            ->with(['characters' => fn ($q) => $q->whereNotNull('image_path')->with(['scenes.episode'])])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function (Project $project) {
                $characters = $project->characters->map(function ($char) {
                    $episodeNames = $char->scenes
                        ->pluck('episode')
                        ->filter()
                        ->unique('id')
                        ->pluck('title')
                        ->filter()
                        ->values()
                        ->all();

                    return [
                        'id' => $char->id,
                        'name' => $char->name,
                        'image_prompt' => $char->image_prompt,
                        'image_url' => $char->image_path ? route('gallery.character-image', $char) : null,
                        'episode_names' => $episodeNames,
                    ];
                })->values()->all();

                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'characters' => $characters,
                ];
            })
            ->filter(fn ($p) => count($p['characters']) > 0)
            ->values()
            ->all();

        return Inertia::render('Gallery/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Serve character image from storage (avoids relying on public/storage symlink).
     */
    public function characterImage(Request $request, Character $character)
    {
        if (! $character->image_path) {
            abort(404);
        }

        $character->load('project');
        if ($character->project->user_id !== $request->user()->id) {
            abort(404);
        }

        $path = Storage::disk('public')->path($character->image_path);
        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path, ['Content-Type' => 'image/png']);
    }
}
