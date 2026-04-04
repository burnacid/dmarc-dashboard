<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthnRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_serialization_is_php_for_webauthn_challenge_storage(): void
    {
        $this->assertSame('php', config('session.serialization'));
    }

    public function test_webauthn_register_options_is_not_blocked_by_csrf(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/webauthn/register/options');

        $response->assertOk();
        $this->assertStringContainsString('challenge', $response->getContent());
    }
}
