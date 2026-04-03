<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_two_factor_enabled_are_redirected_to_the_challenge_after_password_login(): void
    {
        $user = User::factory()->create();
        $user->createTwoFactorAuth();
        $user->confirmTwoFactorAuth($user->makeTwoFactorCode());

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge', absolute: false));
        $response->assertSessionHas('auth.two-factor', fn (array $state) => $state['user_id'] === $user->id);
        $this->assertGuest();
    }

    public function test_users_can_complete_the_two_factor_challenge_after_password_login(): void
    {
        $user = User::factory()->create();
        $user->createTwoFactorAuth();
        $user->confirmTwoFactorAuth($user->makeTwoFactorCode());

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge', absolute: false));

        $this->travel(31)->seconds();

        $response = $this->post('/two-factor-challenge', [
            'code' => $user->fresh()->makeTwoFactorCode(),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_start_and_confirm_two_factor_from_the_security_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('security.two-factor.store'), [
                'password' => 'password',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->twoFactorAuth()->exists());
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->post(route('security.two-factor.confirm'), [
                'code' => $user->makeTwoFactorCode(),
            ])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_users_can_disable_two_factor_from_the_security_page(): void
    {
        $user = User::factory()->create();
        $user->createTwoFactorAuth();
        $user->confirmTwoFactorAuth($user->makeTwoFactorCode());

        $this->actingAs($user)
            ->delete(route('security.two-factor.destroy'), [
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_users_can_delete_their_registered_passkeys(): void
    {
        $user = User::factory()->create();

        $credential = $user->makeWebAuthnCredential([
            'id' => 'test-credential-id',
            'user_id' => (string) Str::uuid(),
            'alias' => 'Office laptop',
            'counter' => 0,
            'rp_id' => 'localhost',
            'origin' => 'http://localhost',
            'transports' => ['internal'],
            'aaguid' => (string) Str::uuid(),
            'public_key' => 'public-key-placeholder',
            'attestation_format' => 'none',
            'disabled_at' => null,
        ]);
        $credential->save();

        $this->actingAs($user)
            ->delete(route('security.passkeys.destroy', $credential), [
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('webauthn_credentials', [
            'id' => 'test-credential-id',
        ]);
    }
}

