<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        // Registration must be enabled in .env for these tests to pass
        // If APP_REGISTRATION_ENABLED=false, these tests will be skipped
        if (!config('app.registration_enabled')) {
            $this->markTestSkipped('Registration is disabled in configuration');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // Registration must be enabled in .env for these tests to pass
        if (!config('app.registration_enabled')) {
            $this->markTestSkipped('Registration is disabled in configuration');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
