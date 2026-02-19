<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\SourceType;
use App\Jobs\GenerateProjectStructureJob;
use App\Models\Book;
use App\Models\Language;
use App\Models\Project;
use App\Services\CreditCalculator;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->where('user_id', auth()->id())
            ->withCount(['episodes', 'characters', 'scenes']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && in_array($request->status, ProjectStatus::values(), true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('dir', 'desc');
        if (in_array($sort, ['created_at', 'title'], true) && in_array($dir, ['asc', 'desc'], true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->latest();
        }

        $projects = $query->paginate((int) $request->get('per_page', 15))->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'sort' => $request->get('sort', 'created_at'),
                'dir' => $request->get('dir', 'desc'),
            ],
            'statusOptions' => array_map(fn (ProjectStatus $s) => ['value' => $s->value, 'label' => $s->label()], ProjectStatus::cases()),
        ]);
    }

    public function show(Project $project, CreditCalculator $creditCalculator, CreditService $creditService)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->loadCount(['episodes', 'characters', 'scenes']);
        $project->load([
            'episodes' => fn ($q) => $q->with(['scenes' => fn ($q) => $q->orderBy('scene_number')])->orderBy('episode_number'),
            'characters',
            'dubLanguages',
            'renderLogs' => fn ($q) => $q->with('episode:id,title')->latest()->limit(100),
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

        $timeline = $this->buildProjectTimeline($project);

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
            'timeline' => $timeline,
            'statusLabel' => $project->status->label(),
            'sourceLabel' => $project->source_type->label(),
            'sceneRegenerationCosts' => [
                'image' => $creditService->sceneImageRegenerationCost(),
                'voice' => $creditService->sceneVoiceRegenerationCost(),
            ],
            'userCredits' => (int) $user->credits,
        ]);
    }

    /**
     * Build a chronological timeline of project activity (created, episodes, key render events).
     */
    private function buildProjectTimeline(Project $project): array
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
                'description' => $lastLog->message,
            ];
        }

        usort($items, fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return array_slice($items, 0, 20);
    }

    public function create(CreditService $creditService)
    {
        $user = auth()->user();
        $plan = $user->plan;

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

        $sceneGenerationCredits = $creditService->sceneGenerationCost();
        $hasEnoughForSceneGeneration = $creditService->hasEnoughForSceneGeneration($user);

        return Inertia::render('Projects/Create', [
            'languages' => Language::active()->get(),
            'books' => Book::orderBy('title')->get(['id', 'title', 'description']),
            'plan' => $planPayload,
            'sceneGenerationCredits' => $sceneGenerationCredits,
            'userCredits' => (int) $user->credits,
            'hasEnoughForSceneGeneration' => $hasEnoughForSceneGeneration,
        ]);
    }

    public function store(Request $request, CreditService $creditService)
    {
        $user = auth()->user();
        $plan = $user->plan;

        $request->validate([
            'source_type' => 'required|in:library,uploaded',
            'title' => 'required|string|max:255',
            'book_id' => 'required_if:source_type,library|nullable|exists:books,id',
            'story' => 'required_if:source_type,uploaded|nullable|string|max:50000',
            'language_id' => 'nullable|exists:languages,id',
            'dub_languages' => 'nullable|array',
            'dub_languages.*' => 'exists:languages,id',
            'video_minutes' => 'nullable|integer|min:1|max:120',
            'quality' => 'nullable|in:1080p,4k',
            'reels_per_episode' => 'nullable|integer|min:0',
            'intro_song' => 'nullable|boolean',
            'background_music' => 'nullable|boolean',
        ]);

        $sourceType = $request->source_type === 'library' ? SourceType::Library : SourceType::Uploaded;

        if ($sourceType === SourceType::Library) {
            $book = Book::findOrFail($request->book_id);
            if (empty(trim($book->content ?? ''))) {
                return back()->withErrors(['book_id' => 'This book has no content yet.'])->withInput();
            }
        } else {
            if (empty(trim($request->story ?? ''))) {
                return back()->withErrors(['story' => 'Please provide your story text.'])->withInput();
            }
        }

        $maxDubbing = $plan?->max_dubbing_languages ?? 1;
        $maxMinutes = $plan?->max_video_minutes ?? 5;
        $maxReels = $plan?->max_reels_per_episode ?? 1;
        $allow4k = $plan?->allow_4k ?? false;

        if (count($request->dub_languages ?? []) > $maxDubbing) {
            return back()->withErrors(['dub_languages' => 'Upgrade your plan for more dubbing languages.'])->withInput();
        }
        if (($request->video_minutes ?? 5) > $maxMinutes) {
            return back()->withErrors(['video_minutes' => 'Video length exceeds your plan limit.'])->withInput();
        }
        if (($request->reels ?? 0) > $maxReels) {
            return back()->withErrors(['reels' => 'Upgrade plan for more reels.'])->withInput();
        }
        if (($request->quality ?? '1080p') === '4k' && ! $allow4k) {
            return back()->withErrors(['quality' => '4K available in Pro plan.'])->withInput();
        }

        if (! $creditService->hasEnoughForSceneGeneration($user)) {
            return back()->withErrors([
                'credits' => 'Insufficient credits for scene generation. Required: ' . $creditService->sceneGenerationCost() . ', available: ' . $user->credits . '.',
            ])->withInput();
        }

        $project = Project::create([
            'user_id' => $user->id,
            'book_id' => $sourceType === SourceType::Library ? $request->book_id : null,
            'title' => $request->title,
            'description' => null,
            'story_source' => $sourceType === SourceType::Uploaded ? $request->story : null,
            'source_type' => $sourceType,
            'is_public' => false,
            'status' => ProjectStatus::Draft,
            'total_credits_used' => 0,
            'language' => 'hindi',
            'video_minutes' => $request->video_minutes ?? 5,
            'quality' => $request->quality ?? '1080p',
            'reels_per_episode' => (int) ($request->reels ?? 0),
            'intro_song' => (bool) ($request->intro_song ?? false),
            'background_music' => (bool) ($request->background_music ?? false),
        ]);

        if (! empty($request->dub_languages)) {
            $project->dubLanguages()->sync($request->dub_languages);
        }

        $creditService->deductForSceneGeneration($user, $project);

        $project->update(['status' => ProjectStatus::Generating]);

        GenerateProjectStructureJob::dispatch($project);

        return redirect()->route('projects.index')->with('success', 'Project created. Scene generation has started.');
    }

    public function clone(Project $project, CreditService $creditService)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        if (! $creditService->hasEnoughForSceneGeneration($user)) {
            return redirect()->route('projects.index')->withErrors([
                'credits' => 'Insufficient credits to clone. Required: ' . $creditService->sceneGenerationCost(),
            ]);
        }

        $clone = Project::create([
            'user_id' => $user->id,
            'book_id' => $project->book_id,
            'title' => $project->title . ' (Copy)',
            'description' => $project->description,
            'story_source' => $project->story_source,
            'source_type' => $project->source_type,
            'is_public' => false,
            'status' => ProjectStatus::Draft,
            'total_credits_used' => 0,
            'language' => $project->language,
            'video_minutes' => $project->video_minutes,
            'quality' => $project->quality,
            'reels_per_episode' => $project->reels_per_episode,
            'intro_song' => $project->intro_song,
            'background_music' => $project->background_music,
        ]);

        $clone->dubLanguages()->sync($project->dubLanguages()->pluck('id')->all());

        $creditService->deductForSceneGeneration($user, $clone);
        $clone->update(['status' => ProjectStatus::Generating]);
        GenerateProjectStructureJob::dispatch($clone);

        return redirect()->route('projects.index')->with('success', 'Project cloned. Scene generation has started.');
    }

    public function archive(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }
        $project->update(['status' => ProjectStatus::Archived]);
        return redirect()->route('projects.index')->with('success', 'Project archived.');
    }

    public function destroy(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
