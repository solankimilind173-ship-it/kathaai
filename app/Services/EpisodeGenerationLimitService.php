<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\User;
use Illuminate\Support\Carbon;

class EpisodeGenerationLimitService
{
    /**
     * Number of episodes the user has generated (created) today, across all projects.
     */
    public function episodesGeneratedToday(User $user): int
    {
        return Episode::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Maximum episodes the user can generate today based on their plan.
     */
    public function maxEpisodesPerDay(User $user): int
    {
        $plan = $user->plan;
        if (! $plan) {
            return 1;
        }
        $value = $plan->max_episodes_per_day ?? null;

        return $value !== null ? (int) $value : 1;
    }

    /**
     * Whether the user can generate at least one more episode today.
     */
    public function canGenerateEpisodesToday(User $user): bool
    {
        return $this->episodesGeneratedToday($user) < $this->maxEpisodesPerDay($user);
    }

    /**
     * How many more episodes the user can generate today (capped at plan limit).
     */
    public function remainingSlotsToday(User $user): int
    {
        $used = $this->episodesGeneratedToday($user);
        $max = $this->maxEpisodesPerDay($user);

        return max(0, $max - $used);
    }

    /**
     * Message when daily limit is reached.
     */
    public function limitReachedMessage(User $user): string
    {
        $max = $this->maxEpisodesPerDay($user);

        return "Your plan allows {$max} episode generation(s) per day. You've reached today's limit. Try again tomorrow or upgrade for more.";
    }
}
