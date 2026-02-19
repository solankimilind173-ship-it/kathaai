<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Jobs\RenderProjectJob;
use App\Models\CreditTransaction;
use App\Models\Project;
use App\Models\RenderLog;
use App\Services\CreditCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RenderController extends Controller
{
    /**
     * Return estimated render cost for the given project and settings (for modal).
     */
    public function estimateCost(Request $request, Project $project, CreditCalculator $creditCalculator)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'resolution' => 'nullable|string|in:1080p,4k',
            'format' => 'nullable|string|in:16:9,9:16',
            'fps' => 'nullable|integer|in:24,30',
            'background_music' => 'nullable|boolean',
            'subtitle_style' => 'nullable|string|max:64',
        ]);

        $durationMinutes = $this->getProjectDurationMinutes($project);

        $cost = $creditCalculator->calculateRenderCost([
            'resolution' => $request->input('resolution', '1080p'),
            'format' => $request->input('format', '16:9'),
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

    /**
     * Start render: deduct credits, create render_log, set project status, dispatch job.
     */
    public function start(Request $request, Project $project, CreditCalculator $creditCalculator)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        $plan = $user->plan;

        $request->validate([
            'resolution' => 'required|string|in:1080p,4k',
            'format' => 'required|string|in:16:9,9:16',
            'fps' => 'required|integer|in:24,30',
            'subtitle_style' => 'nullable|string|max:64',
            'background_music' => 'nullable|boolean',
        ]);

        $resolution = $request->input('resolution', '1080p');
        if ($resolution === '4k' && ! ($plan && $plan->allow_4k)) {
            return back()->withErrors(['resolution' => '4K is not available on your plan.']);
        }

        $durationMinutes = $this->getProjectDurationMinutes($project);
        $amount = $creditCalculator->calculateRenderCost([
            'resolution' => $resolution,
            'format' => $request->input('format'),
            'fps' => $request->input('fps'),
            'duration_minutes' => $durationMinutes,
            'background_music' => (bool) $request->input('background_music', false),
        ]);

        if ($user->credits < $amount) {
            return back()->withErrors([
                'credits' => "Insufficient credits. Required: {$amount}, available: {$user->credits}.",
            ]);
        }

        DB::transaction(function () use ($user, $project, $amount, $request) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'amount' => -$amount,
                'type' => 'usage',
                'feature' => 'project_render',
                'description' => 'Project render',
            ]);

            $user->decrement('credits', $amount);
            $project->increment('total_credits_used', $amount);
        });

        $renderLog = RenderLog::create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $project->update(['status' => ProjectStatus::Rendering]);

        RenderProjectJob::dispatch($project, $renderLog, [
            'resolution' => $request->input('resolution'),
            'format' => $request->input('format'),
            'fps' => (int) $request->input('fps'),
            'subtitle_style' => $request->input('subtitle_style', 'default'),
            'background_music' => (bool) $request->input('background_music', false),
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Render started.');
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
