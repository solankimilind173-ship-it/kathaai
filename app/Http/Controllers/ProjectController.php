<?php

namespace App\Http\Controllers;

use App\Jobs\ExtractCharactersJob;
use App\Jobs\GenerateCharacterImagesJob;
use App\Jobs\GenerateCharacterPromptsJob;
use App\Jobs\GenerateEpisodesJob;
use App\Models\Language;
use App\Models\Project;
use App\Models\StoryChunk;
use App\Services\CreditCalculator;
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

    public function show(Project $project, CreditCalculator $creditCalculator)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->loadCount(['episodes', 'characters']);
        $project->load([
            'episodes' => fn($q) => $q->with(['scenes' => fn($q) => $q->orderBy('scene_number')])->orderBy('id'),
            'dubLanguages',
        ]);

        $user = auth()->user();
        $plan = $user->plan;

        $minutes = $project->video_minutes ?? 5;
        $reels = $project->reels_per_episode ?? 0;
        $dubLanguageIds = $project->dubLanguages->pluck('id')->all();
        $estimatedCredits = $creditCalculator->calculate([
            'minutes' => $minutes,
            'quality' => $project->quality ?? '1080p',
            'reels' => $reels * ($project->episodes_count ?: 1),
            'dub_languages' => $dubLanguageIds,
            'intro_song' => $project->intro_song ?? false,
            'background_music' => $project->background_music ?? false,
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'plan' => $plan,
            'estimatedCredits' => $estimatedCredits,
            'creditOptions' => [
                'video_minutes' => $minutes,
                'quality' => $project->quality ?? '1080p',
                'reels_per_episode' => $reels,
                'dub_languages_count' => count($dubLanguageIds),
                'intro_song' => $project->intro_song ?? false,
                'background_music' => $project->background_music ?? false,
            ],
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $plan = $user->plan;

        // Fallback for users without a plan (e.g. super admin, new accounts)
        $planPayload = $plan
            ? [
                'max_dubbing_languages' => $plan->max_dubbing_languages ?? 1,
                'max_video_minutes' => $plan->max_video_minutes ?? 5,
                'max_reels_per_episode' => $plan->max_reels_per_episode ?? 1,
                'allow_4k' => $plan->allow_4k ?? false,
            ]
            : [
                'max_dubbing_languages' => 1,
                'max_video_minutes' => 5,
                'max_reels_per_episode' => 1,
                'allow_4k' => false,
            ];

        return Inertia::render('Projects/Create', [
            'languages' => Language::active()->get(),
            'plan' => $planPayload,
        ]);
    }

    public function store(Request $request, StoryChunker $chunker)
    {
        $user = auth()->user();
        $plan = $user->plan;

        $maxDubbing = $plan?->max_dubbing_languages ?? 1;
        $maxMinutes = $plan?->max_video_minutes ?? 5;
        $maxReels = $plan?->max_reels_per_episode ?? 1;
        $allow4k = $plan?->allow_4k ?? false;

        if (count($request->dub_languages ?? []) > $maxDubbing) {
            return back()->withErrors([
                'dub_languages' => 'Upgrade your plan for more dubbing languages.'
            ]);
        }

        if (($request->minutes ?? 5) > $maxMinutes) {
            return back()->withErrors([
                'minutes' => 'Video length exceeds your plan limit.'
            ]);
        }

        if (($request->reels ?? 0) > $maxReels) {
            return back()->withErrors([
                'reels' => 'Upgrade plan for more reels.'
            ]);
        }

        if ($request->quality === '4k' && ! $allow4k) {
            return back()->withErrors([
                'quality' => '4K available in Pro plan.'
            ]);
        }
        $maxLanguages = $maxDubbing;

        $request->validate([
            'title' => 'required|string|max:255',
            'language_id' => 'required|exists:languages,id',
            'dub_languages' => 'nullable|array',
            'dub_languages.*' => 'exists:languages,id',
        ]);

        if (count($request->dub_languages ?? []) > $maxLanguages) {
            return back()->withErrors([
                'dub_languages' => "Your plan allows only {$maxLanguages} dubbing language(s). Upgrade your plan."
            ]);
        }

        $project = Project::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'language' => 'hindi',
            'status' => 'processing',
            'video_minutes' => $request->minutes ?? 5,
            'quality' => $request->quality ?? '1080p',
            'reels_per_episode' => $request->reels ?? 0,
            'intro_song' => (bool) ($request->intro_song ?? false),
            'background_music' => (bool) ($request->background_music ?? false),
        ]);

        if ($request->dub_languages) {
            $project->dubLanguages()->sync($request->dub_languages);
        }

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
