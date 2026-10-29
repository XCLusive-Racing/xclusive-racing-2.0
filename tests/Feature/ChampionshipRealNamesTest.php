<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-29: a championship can force real names. Signing up needs
// a real name on the profile, and the server and the championship's pages show it
// whatever the driver picked. On the profile, "Real name" needs a saved name first.
class ChampionshipRealNamesTest extends TestCase
{
    use RefreshDatabase;

    private function championship(bool $realNames, string $slug = 'nlrl'): Championship
    {
        $league = League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'lmu', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'requirements' => ['real_names_required' => $realNames],
        ]);
        $championship->save();

        return $championship;
    }

    public function test_signing_up_needs_a_real_name_when_required(): void
    {
        $championship = $this->championship(true);
        $driver = User::factory()->create(['name' => 'FastGuy']);

        $this->actingAs($driver)->post(route('championships.register', $championship))
            ->assertSessionHas('error', 'FastGuy: this championship races under real names. Add your first and last name on your profile first.');
        $this->assertSame(0, $championship->registrations()->count());

        $driver->update(['first_name' => 'Jan', 'last_name' => 'Jansen']);
        $this->actingAs($driver)->post(route('championships.register', $championship))->assertSessionHas('success');
    }

    public function test_without_the_setting_no_real_name_is_needed(): void
    {
        $championship = $this->championship(false);

        $this->actingAs(User::factory()->create())->post(route('championships.register', $championship))
            ->assertSessionHas('success');
    }

    public function test_the_real_name_is_used_whatever_the_profile_says(): void
    {
        $championship = $this->championship(true);
        $driver = User::factory()->create([
            'name' => 'FastGuy', 'first_name' => 'Jan', 'last_name' => 'Jansen',
            'display_name_preference' => User::DISPLAY_GAMERTAG, 'platform_id' => 'S-1',
        ]);

        $this->assertSame('Jan Jansen', $driver->displayNameIn($championship));
        $this->assertSame('FastGuy', $driver->displayNameIn($this->championship(false, 'other')));
        $this->assertSame('FastGuy', $driver->displayName());

        $round = Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        RaceRegistration::create(['race_id' => $round->id, 'user_id' => $driver->id]);

        $entry = app(AccServerConfigService::class)->entryList($round)['entries'][0];
        $this->assertSame('J. Jansen', $entry['drivers'][0]['lastName']);
    }

    public function test_real_name_preference_needs_a_name(): void
    {
        $user = User::factory()->create(['name' => 'FastGuy']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Real name (save your name first)');

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => 'FastGuy', 'display_name_preference' => User::DISPLAY_REAL_NAME])
            ->assertSessionHasErrors('display_name_preference');
        $this->assertNotSame(User::DISPLAY_REAL_NAME, $user->fresh()->display_name_preference);
    }
}
