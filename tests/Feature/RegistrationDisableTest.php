<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDisableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that registration can be disabled via config
     */
    public function test_registration_can_be_disabled(): void
    {
        // Verify that registration_enabled config exists and is a boolean
        $value = config('app.registration_enabled');
        $this->assertTrue(is_bool($value));
    }

    /**
     * Test that when registration is disabled, GET /register returns 404
     */
    public function test_register_page_returns_404_when_disabled(): void
    {
        // Temporarily set to disabled
        config(['app.registration_enabled' => false]);

        // Direct controller check should reject
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    /**
     * Test that registration form submission is rejected when disabled
     */
    public function test_registration_submission_rejected_when_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test that registration link is hidden on login page when config is false
     */
    public function test_registration_link_hidden_when_disabled_in_config(): void
    {
        config(['app.registration_enabled' => false]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        // When disabled, the link shouldn't be visible
        $response->assertDontSee('Create an account');
    }

    /**
     * Test that the RegisteredUserController checks config
     */
    public function test_controller_rejects_disabled_registration(): void
    {
        config(['app.registration_enabled' => false]);

        // Post directly should be rejected by controller
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}

