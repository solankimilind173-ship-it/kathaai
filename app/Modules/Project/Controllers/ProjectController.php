<?php

namespace App\Modules\Project\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\SourceType;
use App\Jobs\GenerateProjectStructureJob;
use App\Models\Book;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Language;
use App\Models\Project;
use App\Models\ProjectEngagement;
use App\Models\ProjectShareToken;
use App\Models\Scene;
use App\Models\SceneRenderSettings;
use App\Services\CreditCalculator;
use App\Services\CreditService;
use App\Services\NotificationService;
use App\Services\ProjectAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->where('user_id', auth()->id())
            ->withCount(['episodes', 'characters', 'scenes']);

        if ($request->boolean('archived')) {
            $query->archived();
        } else {
            $query->notArchived();
        }

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

        return Inertia::render('Project/Pages/Index', [
            'projects' => $projects,
            'filters' => [
                'archived' => $request->boolean('archived'),
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

    public function show(Project $project, CreditCalculator $creditCalculator, CreditService $creditService, ProjectAnalyticsService $projectAnalyticsService)
    {
        $project->loadCount(['episodes', 'characters', 'scenes']);
        $project->load([
            'episodes' => fn ($q) => $q->with(['scenes' => fn ($q) => $q->orderBy('scene_number')])->orderBy('episode_number'),
            'characters',
            'dubLanguages',
            'shareToken',
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
        $projectAnalytics = $projectAnalyticsService->getAnalytics($project);

        $shareToken = $project->is_public ? $project->shareToken : null;
        $shareUrl = $shareToken ? route('share.show', $shareToken->token) : null;
        $project->loadCount(['renderRunLogs as failed_render_count' => fn ($q) => $q->where('status', 'failed')]);
        $hasFailedRender = ($project->failed_render_count ?? 0) > 0;

        ProjectEngagement::incrementFor($project, 'view_count');

        return Inertia::render('Project/Pages/Show', [
            'project' => $project,
            'shareUrl' => $shareUrl,
            'hasFailedRender' => $hasFailedRender,
            'plan' => $plan,
            'projectAnalytics' => $projectAnalytics,
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
            'viewOnly' => false,
        ]);
    }

    public function updateVisibility(Request $request, Project $project)
    {
        $request->validate(['is_public' => 'required|boolean']);
        $project->update(['is_public' => $request->boolean('is_public')]);
        if ($project->is_public) {
            ProjectEngagement::incrementFor($project, 'share_count');
            $token = $project->shareToken ?? ProjectShareToken::create([
                'project_id' => $project->id,
                'token' => ProjectShareToken::generateUniqueToken(),
            ]);
            $shareUrl = route('share.show', $token->token);
        } else {
            $shareUrl = null;
        }
        return back()->with(['shareUrl' => $shareUrl]);
    }

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
                'description' => $lastLog->message ?? null,
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
            : ['max_dubbing_languages' => 1, 'max_video_minutes' => 5, 'max_reels_per_episode' => 1, 'allow_4k' => false];

        return Inertia::render('Project/Pages/Create', [
            'languages' => Language::active()->get(),
            'books' => Book::orderBy('title')->get(['id', 'title', 'description']),
            'plan' => $planPayload,
            'sceneGenerationCredits' => $creditService->sceneGenerationCost(),
            'userCredits' => (int) $user->credits,
            'hasEnoughForSceneGeneration' => $creditService->hasEnoughForSceneGeneration($user),
        ]);
    }

    public function store(Request $request, CreditService $creditService, NotificationService $notificationService)
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

        $notificationService->sendProjectStepCompleted(
            $user,
            $project,
            'Project created',
            'Your project has been created and structure generation has started.'
        );

        return redirect()->route('projects.index')->with('success', 'Project created. Scene generation has started.');
    }

    public function clone(Project $project)
    {
        if ($project->is_archived) {
            return redirect()->route('projects.index')->withErrors(['project' => 'Cannot duplicate an archived project. Restore it first.']);
        }
        ProjectEngagement::incrementFor($project, 'clone_count');
        $user = auth()->user();

        $clone = DB::transaction(function () use ($project, $user) {
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

            $charMap = [];
            foreach ($project->characters as $char) {
                $newChar = Character::create([
                    'project_id' => $clone->id,
                    'name' => $char->name,
                    'description' => $char->description,
                    'locked_face' => $char->locked_face,
                    'selected_image_id' => $char->selected_image_id,
                    'image_prompt' => $char->image_prompt,
                    'image_path' => $char->image_path,
                ]);
                $charMap[$char->id] = $newChar->id;
            }

            $episodeMap = [];
            $projectId = $project->id;
            $episodes = $project->episodes()->orderBy('episode_number')->with([
                'scenes' => fn ($q) => $q->orderBy('scene_number')->with([
                    'characters',
                    'sceneRenderSettings' => fn ($q2) => $q2->where('project_id', $projectId),
                ]),
            ])->get();

            foreach ($episodes as $ep) {
                $newEp = Episode::create([
                    'project_id' => $clone->id,
                    'title' => $ep->title,
                    'episode_number' => $ep->episode_number,
                    'summary' => $ep->summary,
                    'status' => $ep->status,
                    'total_credits_used' => 0,
                ]);
                $episodeMap[$ep->id] = $newEp->id;
            }

            foreach ($episodes as $ep) {
                $newEpisodeId = $episodeMap[$ep->id] ?? null;
                if (! $newEpisodeId) continue;
                foreach ($ep->scenes as $scene) {
                    $newScene = Scene::create([
                        'episode_id' => $newEpisodeId,
                        'title' => $scene->title,
                        'description' => $scene->description,
                        'image_url' => null,
                        'voice_url' => null,
                        'duration' => $scene->duration ?? 0,
                        'credits_used' => 0,
                        'status' => $scene->status ?? null,
                        'camera_style' => $scene->camera_style,
                        'lighting' => $scene->lighting,
                        'location' => $scene->location,
                        'time_of_day' => $scene->time_of_day,
                        'mood' => $scene->mood,
                        'scene_number' => $scene->scene_number,
                    ]);
                    foreach ($scene->characters as $char) {
                        $newCharId = $charMap[$char->id] ?? null;
                        if ($newCharId) {
                            $newScene->characters()->attach($newCharId, ['action' => $char->pivot->action ?? null]);
                        }
                    }
                    $settings = $scene->sceneRenderSettings->first();
                    if ($settings) {
                        SceneRenderSettings::create([
                            'project_id' => $clone->id,
                            'scene_id' => $newScene->id,
                            'sort_order' => $settings->sort_order,
                            'duration_trimmed' => $settings->duration_trimmed,
                            'transition_style' => $settings->transition_style,
                        ]);
                    }
                }
            }
            return $clone;
        });

        return redirect()->route('projects.index')->with('success', 'Project duplicated. You can edit and generate when ready.');
    }

    public function archive(Project $project)
    {
        $project->update(['is_archived' => true]);
        return redirect()->route('projects.index')->with('success', 'Project archived.');
    }

    public function restore(Project $project)
    {
        $project->update(['is_archived' => false]);
        return redirect()->route('projects.index')->with('success', 'Project restored.');
    }

    public function retryStructure(Project $project)
    {
        if ($project->status !== ProjectStatus::Failed) {
            return redirect()->route('projects.show', $project)->withErrors(['project' => 'Can only retry when project status is Failed.']);
        }
        if ($project->is_archived) {
            return redirect()->route('projects.show', $project)->withErrors(['project' => 'Cannot retry an archived project.']);
        }
        $project->update(['status' => ProjectStatus::Generating]);
        GenerateProjectStructureJob::dispatch($project);
        return redirect()->route('projects.show', $project)->with('success', 'Structure generation has been queued for retry.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
