<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorService;
    }

    public function test_generate_secret_returns_32_character_string(): void
    {
        $secret = $this->service->generateSecret();
        $this->assertNotEmpty($secret);
        $this->assertSame(32, strlen($secret));
    }

    public function test_generate_secret_is_base32_like(): void
    {
        $secret = $this->service->generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_get_qr_code_svg_returns_svg_string(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $secret = $this->service->generateSecret();
        $svg = $this->service->getQRCodeSvg($user, $secret);
        $this->assertStringStartsWith('<?xml', $svg);
        $this->assertStringContainsString('svg', $svg);
    }

    public function test_generate_recovery_codes_returns_requested_count(): void
    {
        $codes = $this->service->generateRecoveryCodes(8);
        $this->assertCount(8, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{10}-[a-zA-Z0-9]{10}$/', $code);
        }
    }

    public function test_has_enabled_two_factor_returns_false_when_not_set(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);
        $this->assertFalse($this->service->hasEnabledTwoFactor($user));
    }

    public function test_has_enabled_two_factor_returns_true_when_set(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => Crypt::encryptString('SECRETKEY'),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->assertTrue($this->service->hasEnabledTwoFactor($user));
    }

    public function test_disable_two_factor_clears_secret_and_recovery_codes(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => Crypt::encryptString('SECRETKEY'),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(['a-b', 'c-d'])),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->service->disableTwoFactor($user);
        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }
}
