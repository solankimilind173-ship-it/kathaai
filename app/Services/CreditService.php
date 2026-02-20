<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Services\ProjectAnalyticsService;
use App\Models\CreditTransaction;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Check balance, deduct credits, and log transaction for any usage action.
     * Use this for every credit-consuming action.
     *
     * @param  int  $credits  Positive amount to deduct
     * @param  Scene|null  $scene  If set, also increment scene.credits_used
     * @throws InsufficientCreditsException
     */
    public function deduct(User $user, Project $project, int $credits, string $actionType, ?Scene $scene = null): void
    {
        if ($user->credits < $credits) {
            throw new InsufficientCreditsException(
                $credits,
                (int) $user->credits,
                "This action requires {$credits} credits. You have {$user->credits}."
            );
        }

        DB::transaction(function () use ($user, $project, $credits, $actionType, $scene) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'scene_id' => $scene?->id,
                'action_type' => $actionType,
                'credits' => $credits,
                'amount' => -$credits,
                'type' => 'usage',
                'feature' => $actionType,
                'description' => $this->actionDescription($actionType),
            ]);

            $user->decrement('credits', $credits);
            $project->increment('total_credits_used', $credits);
            if ($scene) {
                $scene->increment('credits_used', $credits);
            }
        });

        ProjectAnalyticsService::invalidateCache($project);
    }

    /**
     * Deduct credits for initial scene generation.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForSceneGeneration(User $user, Project $project): void
    {
        $credits = $this->sceneGenerationCost();
        $this->deduct($user, $project, $credits, 'scene_generation');
    }

    public function sceneGenerationCost(): int
    {
        return (int) config('ai_costs.scene_generation_initial', 50);
    }

    public function hasEnoughForSceneGeneration(User $user): bool
    {
        return $user->credits >= $this->sceneGenerationCost();
    }

    public function sceneImageRegenerationCost(): int
    {
        return (int) config('ai_costs.scene_image_regeneration', 15);
    }

    public function sceneVoiceRegenerationCost(): int
    {
        return (int) config('ai_costs.scene_voice_regeneration', 10);
    }

    public function hasEnoughForSceneImage(User $user): bool
    {
        return $user->credits >= $this->sceneImageRegenerationCost();
    }

    public function hasEnoughForSceneVoice(User $user): bool
    {
        return $user->credits >= $this->sceneVoiceRegenerationCost();
    }

    /**
     * Deduct credits for scene image regeneration.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForSceneImage(User $user, Scene $scene): void
    {
        $scene->loadMissing('episode.project');
        $project = $scene->episode->project;
        $credits = $this->sceneImageRegenerationCost();
        $this->deduct($user, $project, $credits, 'scene_image_regeneration', $scene);
    }

    /**
     * Deduct credits for scene voice regeneration.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForSceneVoice(User $user, Scene $scene): void
    {
        $scene->loadMissing('episode.project');
        $project = $scene->episode->project;
        $credits = $this->sceneVoiceRegenerationCost();
        $this->deduct($user, $project, $credits, 'scene_voice_regeneration', $scene);
    }

    /**
     * Check balance and deduct credits for project render.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForRender(User $user, Project $project, int $credits): void
    {
        $this->deduct($user, $project, $credits, 'project_render');
    }

    public function hasEnoughCredits(User $user, int $credits): bool
    {
        return $user->credits >= $credits;
    }

    private function actionDescription(string $actionType): string
    {
        return match ($actionType) {
            'scene_generation' => 'Initial scene generation',
            'scene_image_regeneration' => 'Scene image regeneration',
            'scene_voice_regeneration' => 'Scene voice regeneration',
            'project_render' => 'Project render',
            default => ucfirst(str_replace('_', ' ', $actionType)),
        };
    }
}
