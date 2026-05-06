<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemLogUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_logs_index_requires_authentication(): void
    {
        config(['app.system_logs_ui_enabled' => true]);

        $this->get(route('system-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_system_logs_index_returns_404_when_disabled(): void
    {
        config(['app.system_logs_ui_enabled' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('system-logs.index'))
            ->assertNotFound();
    }

    public function test_system_logs_index_and_detail_can_be_rendered_when_enabled(): void
    {
        config(['app.system_logs_ui_enabled' => true]);

        $user = User::factory()->create();

        DB::table('system_logs')->insert([
            'channel' => 'stack',
            'level' => 'error',
            'level_value' => 400,
            'message' => 'SMTP transport timeout while sending notification',
            'context' => json_encode(['job' => 'send-mail']),
            'extra' => json_encode(['pid' => 1234]),
            'logged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $logId = (int) DB::table('system_logs')->value('id');

        $this->actingAs($user)
            ->get(route('system-logs.index'))
            ->assertOk()
            ->assertSee('System Logs')
            ->assertSee('SMTP transport timeout while sending notification');

        $this->actingAs($user)
            ->get(route('system-logs.show', $logId))
            ->assertOk()
            ->assertSee('System log #'.$logId)
            ->assertSee('send-mail');
    }

    public function test_logs_prune_command_removes_old_system_and_auth_diagnostic_logs(): void
    {
        config(['app.system_logs_retention_days' => 30]);

        DB::table('system_logs')->insert([
            [
                'channel' => 'stack',
                'level' => 'warning',
                'level_value' => 300,
                'message' => 'old-system-log',
                'context' => null,
                'extra' => null,
                'logged_at' => now()->subDays(45),
                'created_at' => now()->subDays(45),
                'updated_at' => now()->subDays(45),
            ],
            [
                'channel' => 'stack',
                'level' => 'info',
                'level_value' => 200,
                'message' => 'recent-system-log',
                'context' => null,
                'extra' => null,
                'logged_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ]);

        DB::table('auth_diagnostic_logs')->insert([
            [
                'event' => 'password.attempt',
                'level' => 'info',
                'context' => json_encode(['marker' => 'old-auth-log']),
                'created_at' => now()->subDays(40),
            ],
            [
                'event' => 'password.success',
                'level' => 'info',
                'context' => json_encode(['marker' => 'recent-auth-log']),
                'created_at' => now()->subDays(1),
            ],
        ]);

        $this->artisan('logs:prune')
            ->assertSuccessful();

        $this->assertDatabaseMissing('system_logs', ['message' => 'old-system-log']);
        $this->assertDatabaseHas('system_logs', ['message' => 'recent-system-log']);
        $this->assertDatabaseMissing('auth_diagnostic_logs', ['event' => 'password.attempt']);
        $this->assertDatabaseHas('auth_diagnostic_logs', ['event' => 'password.success']);
    }
}

