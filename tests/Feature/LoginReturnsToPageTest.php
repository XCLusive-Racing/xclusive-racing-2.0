<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// User-directed 2026-10-13: a "log in to see this" link should bring you back to the
// page you were on after logging in, not to your profile.
class LoginReturnsToPageTest extends TestCase
{
    use RefreshDatabase;

    private function logIn(): TestResponse
    {
        $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

        return $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-pass']);
    }

    public function test_logging_in_from_a_page_returns_to_that_page(): void
    {
        $this->get(route('results.index'));
        $this->get(route('login'))->assertOk();

        $this->logIn()->assertRedirect(route('results.index'));
    }

    public function test_the_latest_page_wins_over_an_abandoned_login(): void
    {
        $this->get(route('results.index'));
        $this->get(route('login'));
        $this->get(route('championships.index'));
        $this->get(route('login'));

        $this->logIn()->assertRedirect(route('championships.index'));
    }

    public function test_coming_from_an_auth_page_goes_to_the_profile(): void
    {
        $this->get(route('register'));
        $this->get(route('login'));

        $this->logIn()->assertRedirect(route('profile'));
    }

    public function test_a_page_behind_login_still_returns_to_itself(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('login'));

        $this->logIn()->assertRedirect(route('profile.edit'));
    }
}
