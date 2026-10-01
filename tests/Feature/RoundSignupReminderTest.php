<?php

namespace Tests\Feature;

use App\Console\Commands\RemindRoundSignups;
use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A solo championship entrant still signs up per round; one who hasn't gets a
// single inbox reminder in the 48h before the round.
class RoundSignupReminderTest extends TestCase
{
    use RefreshDatabase;

    private Championship $championship;

    protected function setUp(): void
    {
        parent::setUp();

        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Sunday League', 'game' => 'acc', 'season' => 2026,
            'status' => 'running', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function round(string $startsIn = '+1 day', array $attributes = []): Race
    {
        return Race::create($attributes + [
            'title' => 'Sunday League — Round 1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => now()->modify($startsIn), 'championship_id' => $this->championship->id, 'round_number' => 1,
        ]);
    }

    private function entrant(array $registration = [], array $user = []): User
    {
        $driver = User::factory()->create($user);
        ChampionshipRegistration::create($registration + ['championship_id' => $this->championship->id, 'user_id' => $driver->id]);

        return $driver;
    }

    private function remindersFor(User $user): int
    {
        return Message::where('user_id', $user->id)->where('type', RemindRoundSignups::MESSAGE_TYPE)->count();
    }

    public function test_an_entrant_not_on_the_round_is_reminded_once(): void
    {
        $round = $this->round();
        $missing = $this->entrant();
        $signedUp = $this->entrant();
        RaceRegistration::create(['race_id' => $round->id, 'user_id' => $signedUp->id]);

        $this->artisan('championships:remind-round-signups')->assertSuccessful();
        $this->artisan('championships:remind-round-signups')->assertSuccessful();

        $this->assertSame(1, $this->remindersFor($missing));
        $this->assertSame(0, $this->remindersFor($signedUp));

        $message = Message::where('user_id', $missing->id)->first();
        $this->assertSame($round->id, $message->related_id);
        $this->assertStringContainsString('Sunday League', $message->body);

        // The inbox message links to the event.
        $this->actingAs($missing)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee(route('events.show', $round->id));
    }

    public function test_spectators_teams_pending_and_suspended_entrants_are_not_reminded(): void
    {
        $this->round();
        $spectator = $this->entrant(['is_spectator' => true]);
        $pending = $this->entrant(['approved_at' => null]);
        $suspended = $this->entrant([], ['is_suspended' => true]);

        $this->artisan('championships:remind-round-signups')->assertSuccessful();

        $this->assertSame(0, $this->remindersFor($spectator));
        $this->assertSame(0, $this->remindersFor($pending));
        $this->assertSame(0, $this->remindersFor($suspended));
    }

    public function test_only_open_rounds_within_48_hours_of_a_public_championship_count(): void
    {
        $driver = $this->entrant();
        $this->round('+3 days');
        $this->round('+1 day', ['status' => 'finished']);

        $this->artisan('championships:remind-round-signups')->assertSuccessful();
        $this->assertSame(0, $this->remindersFor($driver));

        $this->championship->update(['visibility' => 'unlisted']);
        $this->round('+1 day');
        $this->artisan('championships:remind-round-signups')->assertSuccessful();
        $this->assertSame(0, $this->remindersFor($driver));
    }

    public function test_dry_run_sends_nothing(): void
    {
        $this->round();
        $driver = $this->entrant();

        $this->artisan('championships:remind-round-signups', ['--dry-run' => true])
            ->expectsOutputToContain("Would remind user {$driver->id}")
            ->assertSuccessful();

        $this->assertSame(0, $this->remindersFor($driver));
    }

    public function test_the_start_time_is_in_the_drivers_own_timezone(): void
    {
        $this->round('+1 day', ['scheduled_at' => now()->addDay()->setTime(18, 0)]);
        $driver = $this->entrant([], ['timezone' => 'America/New_York']);

        $this->artisan('championships:remind-round-signups')->assertSuccessful();

        $body = Message::where('user_id', $driver->id)->value('body');
        $this->assertMatchesRegularExpression('/(EDT|EST)/', $body);
    }
}
