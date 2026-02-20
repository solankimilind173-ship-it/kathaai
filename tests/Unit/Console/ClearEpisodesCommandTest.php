<?php

namespace Tests\Unit\Console;

use App\Models\Episode;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearEpisodesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_episodes_truncates_table(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['user_id' => $user->id, 'title' => 'P']);
        Episode::create(['project_id' => $project->id]);
        Episode::create(['project_id' => $project->id]);
        $this->assertSame(2, Episode::count());

        $this->artisan('app:clear-episodes')
            ->expectsOutput('Episodes cleared!')
            ->assertSuccessful();

        $this->assertSame(0, Episode::count());
    }
}
