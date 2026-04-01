<?php

namespace App\Console\Commands;

use App\Enums\ProjectStatus;
use App\Jobs\RunDailyRenderPipelineJob;
use App\Models\Project;
use Illuminate\Console\Command;

class ScheduleDailyProjectRender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:auto-render';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue the daily auto-render pipeline for eligible projects';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxPerDay = (int) config('kathaai.auto_render_max_per_project_per_day', 1);
        if ($maxPerDay <= 0) {
            $this->info('Auto render is disabled by configuration.');

            return self::SUCCESS;
        }

        $today = now()->toDateString();

        $projects = Project::notArchived()
            ->whereIn('status', [
                ProjectStatus::Ready,
                ProjectStatus::Completed,
                ProjectStatus::Failed,
            ])
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('last_auto_render_at')
                    ->orWhereDate('last_auto_render_at', '<', $today);
            })
            ->get();

        $count = 0;

        foreach ($projects as $project) {
            RunDailyRenderPipelineJob::dispatch($project);
            $count++;
        }

        $this->info("Queued daily render pipeline for {$count} project(s).");

        return self::SUCCESS;
    }
}
