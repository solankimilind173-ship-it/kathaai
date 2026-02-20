<?php

namespace Tests\Unit\Models;

use App\Enums\ProjectStatus;
use App\Enums\SourceType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_not_archived_filters_correctly(): void
    {
        $user = User::factory()->create();
        Project::create(['user_id' => $user->id, 'title' => 'A', 'is_archived' => false]);
        Project::create(['user_id' => $user->id, 'title' => 'B', 'is_archived' => true]);
        Project::create(['user_id' => $user->id, 'title' => 'C', 'is_archived' => false]);

        $notArchived = Project::notArchived()->get();
        $this->assertCount(2, $notArchived);
        $this->assertTrue($notArchived->every(fn ($p) => $p->is_archived === false));
    }

    public function test_scope_archived_filters_correctly(): void
    {
        $user = User::factory()->create();
        Project::create(['user_id' => $user->id, 'title' => 'A', 'is_archived' => true]);
        Project::create(['user_id' => $user->id, 'title' => 'B', 'is_archived' => false]);

        $archived = Project::archived()->get();
        $this->assertCount(1, $archived);
        $this->assertTrue($archived->first()->is_archived);
    }

    public function test_scope_status_filters_by_status(): void
    {
        $user = User::factory()->create();
        Project::create(['user_id' => $user->id, 'title' => 'P1', 'status' => ProjectStatus::Draft]);
        Project::create(['user_id' => $user->id, 'title' => 'P2', 'status' => ProjectStatus::Ready]);
        Project::create(['user_id' => $user->id, 'title' => 'P3', 'status' => ProjectStatus::Draft]);

        $drafts = Project::status(ProjectStatus::Draft)->get();
        $this->assertCount(2, $drafts);
    }

    public function test_scope_for_user_filters_by_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Project::create(['user_id' => $user1->id, 'title' => 'P1']);
        Project::create(['user_id' => $user2->id, 'title' => 'P2']);

        $forUser1 = Project::forUser($user1->id)->get();
        $this->assertCount(1, $forUser1);
        $this->assertSame($user1->id, $forUser1->first()->user_id);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'title' => 'P',
            'status' => 'completed',
        ]);
        $this->assertInstanceOf(ProjectStatus::class, $project->status);
        $this->assertSame(ProjectStatus::Completed, $project->status);
    }

    public function test_source_type_is_cast_to_enum(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'title' => 'P',
            'source_type' => 'library',
        ]);
        $this->assertInstanceOf(SourceType::class, $project->source_type);
        $this->assertSame(SourceType::Library, $project->source_type);
    }

    public function test_credit_usage_summary_attribute(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'title' => 'P',
            'total_credits_used' => 150,
        ]);
        $summary = $project->credit_usage_summary;
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_credits_used', $summary);
        $this->assertSame(150, $summary['total_credits_used']);
    }
}
