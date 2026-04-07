<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_is_publicly_accessible(): void
    {
        $response = $this->get(route('privacy-policy'));

        $response->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Stefan Lenders')
            ->assertSee('Lenders-IT')
            ->assertSee('https://github.com/burnacid/dmarc-dashboard', false)
            ->assertSee('&copy; '.now()->year.' Lenders-IT. All rights reserved.', false);
    }

    public function test_guest_pages_render_the_footer_links(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('href="'.e(route('privacy-policy')).'"', false)
            ->assertSee('href="https://github.com/burnacid/dmarc-dashboard"', false)
            ->assertSee('&copy; '.now()->year.' Lenders-IT. All rights reserved.', false);
    }

    public function test_authenticated_pages_render_the_footer_links(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('href="'.e(route('privacy-policy')).'"', false)
            ->assertSee('href="https://github.com/burnacid/dmarc-dashboard"', false)
            ->assertSee('&copy; '.now()->year.' Lenders-IT. All rights reserved.', false);
    }
}

