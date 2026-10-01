<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Signing up solo for a championship round from the event page needs an approved,
// non-spectator championship entry — otherwise the round's entry list fills up
// with drivers who aren't in the championship at all.
class ChampionshipRoundSoloSignupTest extends TestCase
{
    use RefreshDatabase;

    private Championship $championship;

    private Race $round;

    protected function setUp(): void
    {
        parent::setUp();

        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Race', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $this->round = Race::create([
            'title' => 'Test Race — Round 1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => now()->addDay(), 'championship_id' => $this->championship->id, 'round_number' => 1,
        ]);
    }

    private function entrant(array $registration = []): User
    {
        $driver = User::factory()->create();
        ChampionshipRegistration::create($registration + ['championship_id' => $this->championship->id, 'user_id' => $driver->id]);

        return $driver;
    }

    private function signUp(User $user)
    {
        return $this->actingAs($user)
            ->from(route('events.show', $this->round))
            ->post(route('events.register', $this->round));
    }

    private function isOnRound(User $user): bool
    {
        return RaceRegistration::where('race_id', $this->round->id)->where('user_id', $user->id)->exists();
    }

    public function test_a_championship_entrant_can_sign_up_for_a_round(): void
    {
        $driver = $this->entrant();

        $this->signUp($driver)->assertSessionHas('success');

        $this->assertTrue($this->isOnRound($driver));
    }

    public function test_a_driver_outside_the_championship_cannot_sign_up_for_a_round(): void
    {
        $outsider = User::factory()->create();

        $this->signUp($outsider)->assertSessionHas('error', 'Register for the championship first.');

        $this->assertFalse($this->isOnRound($outsider));
        $this->actingAs($outsider)->get(route('events.show', $this->round))
            ->assertOk()
            ->assertSee('Register for the championship first.')
            ->assertDontSee('REGISTER NOW');
    }

    public function test_spectators_and_pending_entrants_cannot_sign_up_for_a_round(): void
    {
        $spectator = $this->entrant(['is_spectator' => true]);
        $pending = $this->entrant(['approved_at' => null]);

        $this->signUp($spectator)->assertSessionHas('error', 'Register for the championship first.');
        $this->signUp($pending)->assertSessionHas('error', 'Your championship entry is still waiting for approval by the league.');

        $this->assertFalse($this->isOnRound($spectator));
        $this->assertFalse($this->isOnRound($pending));
    }

    public function test_a_standalone_event_needs_no_championship_entry(): void
    {
        $race = Race::create([
            'title' => 'Daily', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addDay(),
        ]);
        $driver = User::factory()->create();

        $this->actingAs($driver)->post(route('events.register', $race))->assertSessionHas('success');

        $this->assertTrue(RaceRegistration::where('race_id', $race->id)->where('user_id', $driver->id)->exists());
    }
}
