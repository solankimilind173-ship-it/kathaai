<?php

namespace App\Modules\Project\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\VideoFormat;
use App\Jobs\RenderProjectJob;
use App\Models\Project;
use App\Models\ProjectEngagement;
use App\Models\RenderLog;
use App\Services\CreditCalculator;
use App\Services\CreditService;
use App\Services\VideoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RenderController extends Controller
{
    public function estimateCost(Request $request, Project $project, CreditCalculator $creditCalculator)
    {
        if ($project->is_archived) {
            abort(403, 'AI actions are not allowed on archived projects. Restore the project first.');
        }
        $request->validate([
            'resolution' => 'nullable|string|in:1080p,4k',
            'format' => 'nullable|string|in:16:9,9:16',
            'video_format' => 'nullable|string|in:instagram_reels,youtube',
            'fps' => 'nullable|integer|in:24,30',
            'background_music' => 'nullable|boolean',
            'subtitle_style' => 'nullable|string|max:64',
        ]);
        $format = $this->resolveFormat($request);
        $durationMinutes = $this->getProjectDurationMinutes($project);
        $cost = $creditCalculator->calculateRenderCost([
            'resolution' => $request->input('resolution', '1080p'),
            'format' => $format,
            'fps' => $request->input('fps', 24),
            'duration_minutes' => $durationMinutes,
            'background_music' => (bool) $request->input('background_music', false),
        ]);
        return response()->json([
            'cost' => $cost,
            'duration_minutes' => $durationMinutes,
            'user_credits' => (int) auth()->user()->credits,
        ]);
    }

    public function start(Request $request, Project $project, CreditCalculator $creditCalculator, CreditService $creditService, VideoMetadataService $metadataService)
    {
        if ($project->is_archived) {
            abort(403, 'AI actions are not allowed on archived projects. Restore the project first.');
        }
        $user = auth()->user();
        $plan = $user->plan;
        $request->validate([
            'resolution' => 'required|string|in:1080p,4k',
            'format' => 'nullable|string|in:16:9,9:16',
            'video_format' => 'required|string|in:instagram_reels,youtube',
            'fps' => 'required|integer|in:24,30',
            'subtitle_style' => 'nullable|string|max:64',
            'background_music' => 'nullable|boolean',
        ]);
        $resolution = $request->input('resolution', '1080p');
        if ($resolution === '4k' && ! ($plan && $plan->allow_4k)) {
            return back()->withErrors(['resolution' => '4K is not available on your plan.']);
        }
        $videoFormat = VideoFormat::from($request->input('video_format'));
        $format = $videoFormat->aspectRatio();
        $durationMinutes = $this->getProjectDurationMinutes($project);
        $amount = $creditCalculator->calculateRenderCost([
            'resolution' => $resolution,
            'format' => $format,
            'fps' => $request->input('fps'),
            'duration_minutes' => $durationMinutes,
            'background_music' => (bool) $request->input('background_music', false),
        ]);
        if (! $creditService->hasEnoughCredits($user, $amount)) {
            return back()->withErrors([
                'credits' => "Insufficient credits. Required: {$amount}, available: {$user->credits}.",
            ]);
        }
        $metadata = $metadataService->generateForProject($project);
        $creditService->deductForRender($user, $project, $amount);
        ProjectEngagement::incrementFor($project, 'render_count');
        $renderLog = RenderLog::create([
            'project_id' => $project->id,
            'status' => 'pending',
            'video_format' => $videoFormat->value,
            'video_title' => $metadata['title'],
            'video_description' => $metadata['description'],
            'hashtags' => $metadata['hashtags'],
        ]);
        $project->update(['status' => ProjectStatus::Rendering]);
        RenderProjectJob::dispatch($project, $renderLog, [
            'resolution' => $resolution,
            'format' => $format,
            'video_format' => $videoFormat->value,
            'fps' => (int) $request->input('fps'),
            'subtitle_style' => $request->input('subtitle_style', 'default'),
            'background_music' => (bool) $request->input('background_music', false),
        ]);
        return redirect()->route('projects.show', $project)->with('success', 'Render started. Title, description and 10 hashtags have been generated for this video.');
    }

    private function resolveFormat(Request $request): string
    {
        if ($request->filled('video_format')) {
            return VideoFormat::from($request->input('video_format'))->aspectRatio();
        }
        return $request->input('format', '16:9');
    }

    public function retryRender(Project $project)
    {
        if ($project->is_archived) {
            return redirect()->route('projects.show', $project)->withErrors(['project' => 'Cannot retry render on an archived project.']);
        }
        $lastFailed = $project->renderRunLogs()->where('status', 'failed')->latest()->first();
        if (! $lastFailed) {
            return redirect()->route('projects.show', $project)->withErrors(['project' => 'No failed render to retry.']);
        }
        $lastFailed->update(['status' => 'pending', 'error_message' => null, 'completed_at' => null]);
        $project->update(['status' => ProjectStatus::Rendering]);
        ProjectEngagement::incrementFor($project, 'render_count');
        RenderProjectJob::dispatch($project, $lastFailed, []);
        return redirect()->route('projects.show', $project)->with('success', 'Render has been queued for retry.');
    }

    private function getProjectDurationMinutes(Project $project): float
    {
        $projectId = $project->id;
        $totalSeconds = $project->scenes()->get()->sum(function ($scene) use ($projectId) {
            $settings = $scene->sceneRenderSettings()->where('project_id', $projectId)->first();
            return $settings?->duration_trimmed ?? $scene->duration ?? 0;
        });
        return round($totalSeconds / 60, 2) ?: (float) ($project->video_minutes ?? 5);
    }
}
