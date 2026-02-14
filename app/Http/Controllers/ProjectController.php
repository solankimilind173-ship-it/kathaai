<?php

namespace App\Http\Controllers;

use App\Jobs\ExtractCharactersJob;
use App\Jobs\GenerateCharacterImagesJob;
use App\Jobs\GenerateCharacterPromptsJob;
use App\Jobs\GenerateEpisodesJob;
use App\Jobs\GenerateScenesJob;
use App\Models\Character;
use App\Models\Project;
use App\Models\StoryChunk;
use App\Services\StoryChunker;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::where('user_id', auth()->id())
            ->withCount(['episodes', 'characters'])
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->loadCount(['episodes', 'characters']);
        $project->load(['episodes' => fn ($q) => $q->orderBy('id')]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    public function store(Request $request, StoryChunker $chunker)
    {
        $request->validate([
            'title' => 'required|min:3',
            'story' => 'required|min:500',
        ]);

        $project = Project::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'language' => 'hindi',
            'status' => 'processing'
        ]);

        // SMART CHUNKING
        $chunks = $chunker->chunk($request->story);

        foreach ($chunks as $index => $chunk) {
            StoryChunk::create([
                'project_id' => $project->id,
                'chunk_text' => $chunk,
                'chunk_order' => $index,
                'token_count' => intval(strlen($chunk) / 4)
            ]);
        }

        GenerateEpisodesJob::dispatch($project);
        ExtractCharactersJob::dispatch($project)->delay(now()->addSeconds(25));
        GenerateCharacterPromptsJob::dispatch($project)->delay(now()->addSeconds(60));
        GenerateCharacterImagesJob::dispatch($project)->delay(now()->addSeconds(110));

        return redirect()->route('projects.index');
    }
}
