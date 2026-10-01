<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Services\ChampionshipRoundEntryService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A championship entry is in every upcoming round automatically and can skip a
// round on its event page; "per round" also lets a driver sign up for the
// championship from a round's page, "championship" only from the championship page.
class ChampionshipRoundEntryTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private int $nextCarNumber = 21;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function manager(): User
    {
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        return $manager->refresh();
    }

    private function makeChampionship(string $scope = 'per_round', array $requirements = []): Championship
    {
        $championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Test Race', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['team_registration_scope' => $scope],
            'requirements' => $requirements,
        ]);
        $championship->save();

        return $championship;
    }

    private function makeRound(Championship $championship, int $number, string $startsIn = '+1 week'): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $number,
            'title' => 'Round '.$number, 'track' => 'Kyalami', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->modify($startsIn),
        ]);
    }

    private function signUp(User $driver, Championship $championship, ?Race $from = null)
    {
        $request = $this->actingAs($driver);
        if ($from) {
            $request = $request->from(route('events.show', $from));
        }

        return $request->post(route('championships.register', $championship), [
            'car_model' => array_key_first(AccCarCatalog::namesWithClass('acc')),
            'car_number' => $this->nextCarNumber++,
        ]);
    }

    private function isOn(Race $round, User $driver): bool
    {
        return RaceRegistration::where('race_id', $round->id)->where('user_id', $driver->id)->exists();
    }

    public function test_a_championship_sign_up_enters_every_upcoming_round_in_both_modes(): void
    {
        foreach (['per_round', 'championship'] as $scope) {
            $championship = $this->makeChampionship($scope);
            $r1 = $this->makeRound($championship, 1);
            $r2 = $this->makeRound($championship, 2);
            $past = tap($this->makeRound($championship, 0, '-1 week'))->update(['status' => 'finished']);
            $driver = User::factory()->create();

            $this->signUp($driver, $championship)->assertSessionHas('success');

            $this->assertTrue($this->isOn($r1, $driver), $scope);
            $this->assertTrue($this->isOn($r2, $driver), $scope);
            $this->assertFalse($this->isOn($past, $driver), $scope);
        }
    }

    public function test_per_round_offers_the_championship_form_on_the_round_page(): void
    {
        $championship = $this->makeChampionship('per_round');
        $round = $this->makeRound($championship, 1);
        $other = $this->makeRound($championship, 2);
        $driver = User::factory()->create();

        $this->actingAs($driver)->get(route('events.show', $round))
            ->assertOk()
            ->assertViewHas('championshipSignupForm', true)
            ->assertSee('Signing up for this round enters you into')
            ->assertSee('name="car_model"', false);

        $this->signUp($driver, $championship, from: $round)->assertRedirect(route('events.show', $round));

        $this->assertDatabaseHas('championship_registrations', ['championship_id' => $championship->id, 'user_id' => $driver->id]);
        $this->assertTrue($this->isOn($round, $driver));
        $this->assertTrue($this->isOn($other, $driver));
    }

    public function test_championship_mode_only_links_to_the_championship(): void
    {
        $championship = $this->makeChampionship('championship');
        $round = $this->makeRound($championship, 1);
        $driver = User::factory()->create();

        $this->actingAs($driver)->get(route('events.show', $round))
            ->assertOk()
            ->assertViewHas('championshipSignupForm', false)
            ->assertSee('Register for the championship first.')
            ->assertDontSee('REGISTER NOW');

        $this->actingAs($driver)->post(route('events.register', $round))
            ->assertSessionHas('error', 'Register for the championship first.');
    }

    public function test_with_manual_approval_the_rounds_wait_for_the_approval(): void
    {
        $championship = $this->makeChampionship('per_round', ['manual_approval_required' => true]);
        $round = $this->makeRound($championship, 1);
        $driver = User::factory()->create();

        $this->signUp($driver, $championship)->assertSessionHas('success');
        $this->assertFalse($this->isOn($round, $driver));

        $registration = ChampionshipRegistration::where('user_id', $driver->id)->first();
        $this->actingAs($this->manager())
            ->post(route('admin.leagues.championships.entries.approve', [$this->league, $championship, $registration]))
            ->assertSessionHas('success');

        $this->assertTrue($this->isOn($round, $driver));
    }

    public function test_a_skipped_round_stays_skipped_until_the_driver_signs_back_up(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship, 1);
        $driver = User::factory()->create();
        $this->signUp($driver, $championship);

        $this->actingAs($driver)->delete(route('events.unregister', $round))->assertSessionHas('success');
        $this->assertFalse($this->isOn($round, $driver));

        app(ChampionshipRoundEntryService::class)->syncChampionship($championship);
        $this->assertFalse($this->isOn($round, $driver));

        $this->actingAs(User::factory()->create())->get(route('events.show', $round))
            ->assertOk()
            ->assertSee('SKIPPING THIS ROUND')
            ->assertSee($driver->displayName());

        $this->actingAs($driver)->get(route('events.show', $round))->assertSee('REGISTER NOW');
        $this->actingAs($driver)->post(route('events.register', $round))->assertSessionHas('success');
        $this->assertTrue($this->isOn($round, $driver));
    }

    public function test_withdrawing_leaves_the_open_rounds_and_signing_up_again_enters_them_again(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship, 1);
        $driver = User::factory()->create();
        $this->signUp($driver, $championship);

        $this->actingAs($driver)->delete(route('championships.unregister', $championship))->assertSessionHas('success');
        $this->assertFalse($this->isOn($round, $driver));

        $this->signUp($driver, $championship)->assertSessionHas('success');
        $this->assertTrue($this->isOn($round, $driver));
    }

    public function test_a_round_added_later_gets_every_entry(): void
    {
        $championship = $this->makeChampionship();
        $driver = User::factory()->create();
        $this->signUp($driver, $championship);

        $this->actingAs($this->manager())
            ->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
                'track' => 'Spa', 'scheduled_at' => now()->addWeeks(3)->startOfHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->assertTrue($this->isOn($championship->rounds()->where('track', 'Spa')->firstOrFail(), $driver));
    }

    // The repair buttons: entries that should be in a round but aren't (a round set
    // up wrong, entries from before automatic entry) go in; a skip is kept.
    public function test_the_admin_buttons_enter_missing_entries_and_keep_skips(): void
    {
        $championship = $this->makeChampionship();
        $r1 = $this->makeRound($championship, 1);
        $r2 = $this->makeRound($championship, 2);
        $missing = User::factory()->create();
        $skipper = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $missing->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $skipper->id]);
        RaceRegistration::create(['race_id' => $r1->id, 'user_id' => $skipper->id])->delete();
        $admin = $this->manager();

        $this->actingAs($admin)
            ->post(route('admin.leagues.championships.rounds.sync-entries', [$this->league, $championship, $r1]))
            ->assertSessionHas('success', '1 entry added to Round 1.');
        $this->assertTrue($this->isOn($r1, $missing));
        $this->assertFalse($this->isOn($r1, $skipper));
        $this->assertFalse($this->isOn($r2, $missing));

        $this->actingAs($admin)
            ->post(route('admin.leagues.championships.entries.sync-rounds', [$this->league, $championship]))
            ->assertSessionHas('success', '2 entries added to every upcoming round.');
        $this->assertTrue($this->isOn($r2, $missing));
        $this->assertTrue($this->isOn($r2, $skipper));
        $this->assertFalse($this->isOn($r1, $skipper));
    }

    public function test_the_sync_command_dry_run_changes_nothing(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship, 1);
        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);

        $this->artisan('championships:sync-round-entries', ['--dry-run' => true])
            ->expectsOutputToContain('would add 1')
            ->assertSuccessful();
        $this->assertFalse($this->isOn($round, $driver));

        $this->artisan('championships:sync-round-entries')->expectsOutputToContain('added 1')->assertSuccessful();
        $this->assertTrue($this->isOn($round, $driver));
    }

    public function test_the_championship_page_counts_drivers_and_spectators_apart(): void
    {
        $championship = $this->makeChampionship();
        $championship->update(['max_drivers' => 45]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => User::factory()->create()->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => User::factory()->create()->id, 'is_spectator' => true]);

        $this->get(route('championships.show', $championship))
            ->assertOk()
            ->assertSee('1/45 · 1 spectator');
    }
}
