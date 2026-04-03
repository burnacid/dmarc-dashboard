<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests are redirected to login
     */
    public function test_guests_are_redirected_from_home_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Test that authenticated users are redirected to dashboard
     */
    public function test_authenticated_users_are_redirected_from_home_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test that home route has a name
     */
    public function test_home_route_has_name(): void
    {
        $this->assertTrue(route('home') !== null);
    }
}
