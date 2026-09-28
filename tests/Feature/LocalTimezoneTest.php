<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: event times show in the viewer's own timezone — detected from
// the browser, or set by hand in the profile (holiday, VPN). The page renders UK time
// with the exact instant in datetime=""; resources/js/components/local-time.js converts it.
class LocalTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_driver_can_set_and_clear_their_timezone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'timezone' => 'America/New_York'])
            ->assertSessionHasNoErrors();
        $this->assertSame('America/New_York', $user->fresh()->timezone);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'timezone' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->timezone);
    }

    public function test_the_clock_is_24_hour_unless_a_driver_picks_12_hour(): void
    {
        $user = User::factory()->create();
        $race = Race::create([
            'title' => 'Daily Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => Carbon::parse('2026-10-01 19:00', 'UTC'),
        ]);

        $this->actingAs($user)->get(route('events.show', $race))
            ->assertSee('<meta name="xcl-clock" content="24h">', false)
            ->assertSee('Thu 01 Oct 2026 · 20:00 BST');

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'uses_12_hour_clock' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->uses_12_hour_clock);

        $this->actingAs($user->fresh())->get(route('events.show', $race))
            ->assertSee('<meta name="xcl-clock" content="12h">', false)
            ->assertSee('Thu 01 Oct 2026 · 8:00 PM BST');
    }

    public function test_an_unknown_timezone_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'timezone' => 'Mars/Olympus_Mons'])
            ->assertSessionHasErrors('timezone');
    }

    public function test_the_profile_timezone_reaches_the_page_and_event_times_carry_their_instant(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['timezone' => 'Australia/Sydney'])->save();
        $race = Race::create([
            'title' => 'Daily Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => Carbon::parse('2026-10-01 19:00', 'UTC'),
        ]);

        $this->actingAs($user)->get(route('events.show', $race))
            ->assertOk()
            ->assertSee('<meta name="xcl-timezone" content="Australia/Sydney">', false)
            ->assertSee('datetime="2026-10-01T20:00:00+01:00"', false)
            // The official UK time stays on the registration page.
            ->assertSee('(UK: 20:00 BST)');
    }
}
