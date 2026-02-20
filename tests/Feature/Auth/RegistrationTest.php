<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_send_otp_creates_pending_registration_and_redirects_back(): void
    {
        Mail::fake();

        $response = $this->post(route('register.send-otp'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('otp_sent', true);
        $response->assertSessionHas('pending_registration');
        $this->assertNotNull(Cache::get('registration_otp:test@example.com'));
    }

    public function test_new_users_can_register_after_otp_verification(): void
    {
        Mail::fake();

        $this->post(route('register.send-otp'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $otp = Cache::get('registration_otp:test@example.com');
        $this->assertNotNull($otp);

        $response = $this->post(route('register'), [
            'email' => 'test@example.com',
            'otp' => $otp,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
