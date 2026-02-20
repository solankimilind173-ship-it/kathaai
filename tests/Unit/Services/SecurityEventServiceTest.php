<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SecurityEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityEventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SecurityEventService;
    }

    public function test_log_creates_security_event(): void
    {
        $user = User::factory()->create();
        $event = $this->service->log($user, SecurityEventService::LOGIN, null, ['source' => 'test']);
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame(SecurityEventService::LOGIN, $event->event_type);
        $this->assertSame(['source' => 'test'], $event->metadata);
        $this->assertDatabaseHas('security_events', [
            'user_id' => $user->id,
            'event_type' => SecurityEventService::LOGIN,
        ]);
    }

    public function test_get_recent_for_user_returns_user_events(): void
    {
        $user = User::factory()->create();
        $this->service->log($user, SecurityEventService::LOGIN);
        $this->service->log($user, SecurityEventService::LOGOUT);
        $recent = $this->service->getRecentForUser($user, 10);
        $this->assertCount(2, $recent);
        $types = $recent->pluck('event_type')->all();
        $this->assertContains(SecurityEventService::LOGIN, $types);
        $this->assertContains(SecurityEventService::LOGOUT, $types);
    }
}
