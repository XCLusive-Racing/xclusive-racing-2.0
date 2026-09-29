<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A driver can add their real name and choose on their profile whether the site
// shows it or their gamertag; without a real name it's always the gamertag.
class UserDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamertag_is_shown_by_default(): void
    {
        $user = User::factory()->create(['name' => 'FastGuy#1234', 'first_name' => 'Jan', 'last_name' => 'Jansen']);

        $this->assertSame('FastGuy', $user->fresh()->displayName());
    }

    public function test_real_name_is_shown_when_chosen(): void
    {
        $user = User::factory()->create([
            'name' => 'FastGuy', 'first_name' => 'Jan', 'last_name' => 'de Vries',
            'display_name_preference' => User::DISPLAY_REAL_NAME,
        ]);

        $this->assertSame('Jan de Vries', $user->displayName());
        $this->assertSame('FastGuy', $user->gamertag());
    }

    public function test_real_name_chosen_but_empty_falls_back_to_the_gamertag(): void
    {
        $user = User::factory()->create([
            'name' => 'FastGuy', 'first_name' => ' ', 'last_name' => null,
            'display_name_preference' => User::DISPLAY_REAL_NAME,
        ]);

        $this->assertSame('FastGuy', $user->displayName());
    }

    public function test_driver_sets_real_name_and_preference_on_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'FastGuy']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'FastGuy',
                'first_name' => 'Jan',
                'last_name' => 'Jansen',
                'display_name_preference' => User::DISPLAY_REAL_NAME,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('Jan Jansen', $user->displayName());

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => 'FastGuy', 'display_name_preference' => 'bogus'])
            ->assertSessionHasErrors('display_name_preference');
    }

    public function test_profile_page_shows_the_new_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Show My Name As')
            ->assertSee('name="first_name"', false);
    }
}
