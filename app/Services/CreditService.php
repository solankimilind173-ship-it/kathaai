<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Deduct credits for initial scene generation and record on project.
     * Blocks (throws) if user has insufficient credits.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForSceneGeneration(User $user, Project $project): void
    {
        $amount = (int) config('ai_costs.scene_generation_initial', 50);

        if ($user->credits < $amount) {
            throw new InsufficientCreditsException(
                $amount,
                (int) $user->credits,
                "Scene generation requires {$amount} credits. You have {$user->credits}."
            );
        }

        DB::transaction(function () use ($user, $project, $amount) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'amount' => -$amount,
                'type' => 'usage',
                'feature' => 'scene_generation',
                'description' => 'Initial scene generation',
            ]);

            $user->decrement('credits', $amount);
            $project->increment('total_credits_used', $amount);
        });
    }

    /**
     * Return the number of credits required for initial scene generation.
     */
    public function sceneGenerationCost(): int
    {
        return (int) config('ai_costs.scene_generation_initial', 50);
    }

    /**
     * Check if user has at least the required credits for scene generation.
     */
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
     * Deduct credits for scene image regeneration. Updates scene, episode and project credits.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForSceneImage(User $user, Scene $scene): void
    {
        $amount = $this->sceneImageRegenerationCost();
        $this->deductForSceneAction($user, $scene, $amount, 'scene_image_regeneration', 'Scene image regeneration');
    }

    /**
     * Deduct credits for scene voice regeneration.
     *
     * @throws InsufficientCreditsException
     */
    public function deductForSceneVoice(User $user, Scene $scene): void
    {
        $amount = $this->sceneVoiceRegenerationCost();
        $this->deductForSceneAction($user, $scene, $amount, 'scene_voice_regeneration', 'Scene voice regeneration');
    }

    /**
     * @throws InsufficientCreditsException
     */
    private function deductForSceneAction(User $user, Scene $scene, int $amount, string $feature, string $description): void
    {
        if ($user->credits < $amount) {
            throw new InsufficientCreditsException(
                $amount,
                (int) $user->credits,
                "This action requires {$amount} credits. You have {$user->credits}."
            );
        }

        $scene->loadMissing('episode.project');

        $project = $scene->episode->project;

        DB::transaction(function () use ($user, $scene, $project, $amount, $feature, $description) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'scene_id' => $scene->id,
                'amount' => -$amount,
                'type' => 'usage',
                'feature' => $feature,
                'description' => $description,
            ]);

            $user->decrement('credits', $amount);
            $project->increment('total_credits_used', $amount);
            $scene->increment('credits_used', $amount);
        });
    }
}
