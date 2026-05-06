<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminToolsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.auth_diagnostics_enabled', true);
        config()->set('app.system_logs_ui_enabled', true);
    }

    public function test_non_admin_user_cannot_access_admin_tools_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('auth-diagnostics.index'))->assertForbidden();
        $this->actingAs($user)->get(route('system-logs.index'))->assertForbidden();
    }

    public function test_admin_user_can_access_admin_tools_routes(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->get(route('auth-diagnostics.index'))
            ->assertOk()
            ->assertSee('Auth Diagnostic Logs');

        $this->actingAs($admin)->get(route('system-logs.index'))
            ->assertOk()
            ->assertSee('System Logs');
    }

    public function test_sidebar_hides_admin_tools_for_non_admin_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Auth Logs')
            ->assertDontSee('System Logs');
    }

    public function test_sidebar_shows_admin_tools_for_admin_user(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Auth Logs')
            ->assertSee('System Logs');
    }
}

