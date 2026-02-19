<?php

namespace App\Http\Controllers;

use App\Jobs\RegenerateSceneImageJob;
use App\Jobs\RegenerateSceneVoiceJob;
use App\Models\Scene;
use App\Services\CreditService;
use Illuminate\Http\Request;

class SceneController extends Controller
{
    /**
     * Return credit costs for scene regeneration (for showing before user confirms).
     */
    public function costs(CreditService $creditService): array
    {
        return [
            'scene_image_regeneration' => $creditService->sceneImageRegenerationCost(),
            'scene_voice_regeneration' => $creditService->sceneVoiceRegenerationCost(),
        ];
    }

    public function update(Request $request, Scene $scene)
    {
        $scene->load('episode.project');
        if ($scene->episode->project->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'description' => 'nullable|string|max:10000',
            'camera_style' => 'nullable|string|max:64',
            'lighting' => 'nullable|string|max:64',
            'duration' => 'nullable|integer|min:0|max:3600',
        ]);

        $scene->update($request->only(['description', 'camera_style', 'lighting', 'duration']));

        return back()->with('success', 'Scene updated.');
    }

    public function regenerateImage(Scene $scene, CreditService $creditService)
    {
        $scene->load('episode.project');
        $project = $scene->episode->project;
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        if (! $creditService->hasEnoughForSceneImage($user)) {
            return back()->withErrors([
                'credits' => 'Insufficient credits. Required: ' . $creditService->sceneImageRegenerationCost(),
            ]);
        }

        $creditService->deductForSceneImage($user, $scene);

        $scene->update(['status' => 'regenerating_image']);
        RegenerateSceneImageJob::dispatch($scene);

        return redirect()->route('projects.show', $project)->with('success', 'Scene image regeneration started.');
    }

    public function regenerateVoice(Scene $scene, CreditService $creditService)
    {
        $scene->load('episode.project');
        $project = $scene->episode->project;
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        if (! $creditService->hasEnoughForSceneVoice($user)) {
            return back()->withErrors([
                'credits' => 'Insufficient credits. Required: ' . $creditService->sceneVoiceRegenerationCost(),
            ]);
        }

        $creditService->deductForSceneVoice($user, $scene);

        $scene->update(['status' => 'regenerating_voice']);
        RegenerateSceneVoiceJob::dispatch($scene);

        return redirect()->route('projects.show', $project)->with('success', 'Scene voice regeneration started.');
    }
}
