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
use App\Models\VideoStyle;
use App\Services\CreditCalculator;
use App\Services\CreditService;
use App\Services\NotificationService;
use App\Services\ProjectAnalyticsService;
use App\Services\StoryFileExtractor;
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
            $query->where('title', 'like', '%'.$request->search.'%');
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
            'book' => fn ($q) => $q->select('id', 'title', 'user_id', 'is_public'),
            'episodes' => fn ($q) => $q->with(['scenes' => fn ($q) => $q->orderBy('scene_number')])->orderBy('episode_number'),
            'characters' => fn ($q) => $q->with('selectedImage'),
            'dubLanguages',
            'shareToken',
            'renderLogs' => fn ($q) => $q->with('episode:id,title')->latest()->limit(100),
            'renderRunLogs' => fn ($q) => $q->latest()->limit(50),
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
            'subtitleStyles' => [
                ['value' => 'default', 'label' => 'Default'],
                ['value' => 'minimal', 'label' => 'Minimal'],
                ['value' => 'bold', 'label' => 'Bold'],
                ['value' => 'outline', 'label' => 'Outline'],
            ],
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
        $isPublic = $request->boolean('is_public');
        $project->update(['is_public' => $isPublic]);

        if ($project->book_id && $project->book && $project->book->user_id !== null) {
            $project->book->update(['is_public' => $isPublic]);
        }

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

    public function updateSubtitleSettings(Request $request, Project $project)
    {
        $request->validate([
            'default_subtitles_enabled' => 'nullable|boolean',
            'default_subtitle_style' => 'nullable|string|max:64',
        ]);

        $project->update([
            'default_subtitles_enabled' => $request->boolean('default_subtitles_enabled', true),
            'default_subtitle_style' => $request->input('default_subtitle_style', 'default'),
        ]);

        return back()->with('success', 'Subtitle settings saved.');
    }

    public function create(CreditService $creditService)
    {
        $user = auth()->user();
        $plan = $user->plan;
        $demoProjectEligible = $user->isEligibleForDemoProject();
        $episodeLimitService = app(\App\Services\EpisodeGenerationLimitService::class);
        $planPayload = $plan
            ? [
                'max_dubbing_languages' => $plan->max_dubbing_languages ?? 1,
                'max_video_minutes' => $plan->max_video_minutes ?? 5,
                'max_reels_per_episode' => $plan->max_reels_per_episode ?? 1,
                'allow_trailer_generation' => $plan->allow_trailer_generation ?? false,
                'allow_4k' => $plan->allow_4k ?? false,
                'max_episodes_per_day' => $episodeLimitService->maxEpisodesPerDay($user),
            ]
            : ['max_dubbing_languages' => 1, 'max_video_minutes' => 5, 'max_reels_per_episode' => 1, 'allow_trailer_generation' => false, 'allow_4k' => false, 'max_episodes_per_day' => 1];

        $videoFrames = config('video_types.frames', []);
        $videoTypes = VideoStyle::orderBy('name')->get()->map(function (VideoStyle $style) {
            return [
                'id' => (string) $style->id,
                'name' => $style->name,
                'description' => $style->description ?? null,
                'sample_image_url' => $style->sample_image_url ?? null,
            ];
        })->values()->all();

        $sceneGenerationCredits = $demoProjectEligible ? 0 : $creditService->sceneGenerationCost();
        $hasEnoughForSceneGeneration = $demoProjectEligible ? true : $creditService->hasEnoughForSceneGeneration($user);

        return Inertia::render('Project/Pages/Create', [
            'languages' => Language::active()->get(),
            'books' => Book::forDropdown($user->id)->get(['id', 'title', 'description']),
            'plan' => $planPayload,
            'videoFrames' => $videoFrames,
            'videoTypes' => $videoTypes,
            'episodeLimit' => [
                'max_per_day' => $planPayload['max_episodes_per_day'],
                'used_today' => $episodeLimitService->episodesGeneratedToday($user),
                'remaining_today' => $episodeLimitService->remainingSlotsToday($user),
            ],
            'sceneGenerationCredits' => $sceneGenerationCredits,
            'userCredits' => (int) $user->credits,
            'hasEnoughForSceneGeneration' => $hasEnoughForSceneGeneration,
            'demoProjectEligible' => $demoProjectEligible,
        ]);
    }

    public function store(Request $request, CreditService $creditService, NotificationService $notificationService, StoryFileExtractor $fileExtractor)
    {
        $user = auth()->user();
        $plan = $user->plan;
        $isFreeDemoProject = $user->isEligibleForDemoProject();

        $rules = [
            'source_type' => 'required|in:library,uploaded',
            'title' => 'required|string|max:255',
            'book_id' => 'required_if:source_type,library|nullable|exists:books,id',
            'story' => 'nullable|string|max:50000',
            'story_file' => 'nullable|file|max:'.(StoryFileExtractor::MAX_FILE_SIZE_MB * 1024).'|mimes:pdf,doc,docx',
            'language_id' => 'nullable|exists:languages,id',
            'dub_languages' => 'nullable|array',
            'dub_languages.*' => 'exists:languages,id',
            'video_minutes' => 'nullable|integer|min:1|max:120',
            'quality' => 'nullable|in:1080p,4k',
            'video_frame' => 'nullable|string|max:16',
            'video_type' => 'nullable|exists:video_styles,id',
            'reels_per_episode' => 'nullable|integer|min:0',
            'intro_song' => 'nullable|boolean',
            'background_music' => 'nullable|boolean',
            'default_video_format' => 'nullable|string|in:instagram_reels,youtube',
            'default_fps' => 'nullable|integer|in:24,30',
            'default_subtitle_style' => 'nullable|string|max:64',
            'default_subtitles_enabled' => 'nullable|boolean',
        ];

        $request->validate($rules);

        $sourceType = $request->source_type === 'library' ? SourceType::Library : SourceType::Uploaded;
        $storyText = null;
        $bookId = null;
        $storyFromFile = false;

        if ($sourceType === SourceType::Library) {
            $book = Book::findOrFail($request->book_id);
            if (empty(trim($book->content ?? ''))) {
                return back()->withErrors(['book_id' => 'This book has no content yet.'])->withInput();
            }
        } else {
            $storyText = trim($request->story ?? '');
            $storyFile = $request->file('story_file');

            if ($storyFile) {
                $validationErrors = $fileExtractor->validate($storyFile);
                if ($validationErrors !== []) {
                    return back()->withErrors(['story_file' => $validationErrors[0] ?? 'Invalid file.'])->withInput();
                }
                $extracted = $fileExtractor->extract($storyFile);
                if ($extracted !== '') {
                    $storyText = $extracted;
                }
            }

            if (empty($storyText)) {
                return back()->withErrors([
                    'story' => $request->file('story_file')
                        ? 'Could not extract text from the file. Please use a PDF or Word document with readable text, or paste your story below.'
                        : 'Please provide your story text or upload a PDF or Word (.doc, .docx) file.',
                ])->withInput();
            }
        }

        if ($sourceType === SourceType::Uploaded && $request->file('story_file')) {
            $book = Book::create([
                'title' => $request->title ?: $request->file('story_file')->getClientOriginalName(),
                'description' => null,
                'content' => $storyText,
                'user_id' => $user->id,
                'is_public' => false,
            ]);
            $bookId = $book->id;
            $storyFromFile = true;
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
        if (! $isFreeDemoProject && ! $creditService->hasEnoughForSceneGeneration($user)) {
            return back()->withErrors([
                'credits' => 'Insufficient credits for scene generation. Required: '.$creditService->sceneGenerationCost().', available: '.$user->credits.'.',
            ])->withInput();
        }

        $episodeLimitService = app(\App\Services\EpisodeGenerationLimitService::class);
        if (! $episodeLimitService->canGenerateEpisodesToday($user)) {
            return back()->withErrors([
                'episodes' => $episodeLimitService->limitReachedMessage($user),
            ])->withInput();
        }

        $project = Project::create([
            'user_id' => $user->id,
            'book_id' => $sourceType === SourceType::Library ? $request->book_id : $bookId,
            'title' => $request->title,
            'description' => null,
            'story_source' => $sourceType === SourceType::Uploaded ? $storyText : null,
            'source_type' => $sourceType,
            'story_from_file' => $storyFromFile,
            'is_public' => false,
            'status' => ProjectStatus::Draft,
            'total_credits_used' => 0,
            'language' => 'hindi',
            'video_minutes' => $request->video_minutes ?? 5,
            'quality' => $request->quality ?? '1080p',
            'video_frame' => $request->filled('video_frame') ? $request->video_frame : null,
            'video_type' => $request->filled('video_type') ? $request->video_type : null,
            'reels_per_episode' => (int) ($request->reels ?? 0),
            'intro_song' => (bool) ($request->intro_song ?? false),
            'background_music' => (bool) ($request->background_music ?? false),
            'default_video_format' => $request->input('default_video_format') ?: 'youtube',
            'default_fps' => $request->input('default_fps') ?: 24,
            'default_subtitle_style' => $request->input('default_subtitle_style') ?: 'default',
            'default_subtitles_enabled' => $request->boolean('default_subtitles_enabled', true),
        ]);

        if (! empty($request->dub_languages)) {
            $project->dubLanguages()->sync($request->dub_languages);
        }

        if (! $isFreeDemoProject) {
            $creditService->deductForSceneGeneration($user, $project);
        }

        $project->update(['status' => ProjectStatus::Generating]);
        GenerateProjectStructureJob::dispatch($project);

        if ($user->onboarding_status !== 'completed') {
            $user->forceFill([
                'onboarding_status' => 'in_progress',
                'last_onboarding_step' => 'demo-project-created',
            ])->save();
        }

        $notificationService->sendProjectStepCompleted(
            $user,
            $project,
            'Project created',
            $isFreeDemoProject
                ? 'Your free demo project has been created and structure generation has started.'
                : 'Your project has been created and structure generation has started.'
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', $isFreeDemoProject
                ? 'Your free demo project is ready. Scene generation has started, and we will guide you through the next step.'
                : 'Project created. Scene generation has started.');
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
                'title' => $project->title.' (Copy)',
                'description' => $project->description,
                'story_source' => $project->story_source,
                'source_type' => $project->source_type,
                'story_from_file' => $project->story_from_file,
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
            $clone->dubLanguages()->sync($project->dubLanguages()->get()->pluck('id')->all());

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
                if (! $newEpisodeId) {
                    continue;
                }
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
        $episodeLimitService = app(\App\Services\EpisodeGenerationLimitService::class);
        if (! $episodeLimitService->canGenerateEpisodesToday($project->user)) {
            return redirect()->route('projects.show', $project)->withErrors([
                'episodes' => $episodeLimitService->limitReachedMessage($project->user),
            ]);
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
