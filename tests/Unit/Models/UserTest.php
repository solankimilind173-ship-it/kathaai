<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_preference_returns_default_when_not_set(): void
    {
        $user = User::factory()->create(['preferences' => null]);
        $this->assertTrue($user->getPreference(User::PREF_EMAIL_WELCOME));
        $this->assertSame('default', $user->getPreference('unknown_key', 'default'));
    }

    public function test_get_preference_returns_stored_value(): void
    {
        $user = User::factory()->create([
            'preferences' => [
                'email_welcome' => false,
                'custom' => 'value',
            ],
        ]);
        $this->assertFalse($user->getPreference(User::PREF_EMAIL_WELCOME));
        $this->assertSame('value', $user->getPreference('custom', true));
    }

    public function test_has_two_factor_enabled_returns_false_when_not_confirmed(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_has_two_factor_enabled_returns_true_when_confirmed(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_is_suspended_returns_false_when_suspended_at_null(): void
    {
        $user = User::factory()->create(['suspended_at' => null]);
        $this->assertFalse($user->isSuspended());
    }

    public function test_is_suspended_returns_true_when_suspended_at_set(): void
    {
        $user = User::factory()->create(['suspended_at' => now()]);
        $this->assertTrue($user->isSuspended());
    }
}
