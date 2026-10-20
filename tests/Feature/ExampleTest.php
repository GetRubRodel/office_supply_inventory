<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The landing page redirects guests to the admin login page.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    /**
     * Authenticated users are sent straight to the dashboard.
     */
    public function test_authenticated_user_is_redirected_to_dashboard(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }
}
