<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\SessionFormat;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback (NLRL, 2026-09): per-league race formats, picked per round, so
// rounds can differ in session lengths and in how many races they run.
class SessionFormatTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = $this->makeLeague('nlrl');
        $this->manager = $this->managerOf($this->league);
    }

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function managerOf(League $league): User
    {
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        return $manager->refresh();
    }

    private function makeFormat(League $league, array $overrides = []): SessionFormat
    {
        return SessionFormat::withoutTenantScope()->create(array_merge([
            'league_id' => $league->id, 'name' => 'Double Header', 'practice_duration' => 10,
            'qualifying_duration' => 15, 'race_durations' => [25, 20], 'pitstop_count' => 0,
            'fixed_stop_time' => false, 'tyre_set_count' => 4,
        ], $overrides));
    }

    public function test_a_league_manager_creates_and_edits_a_format(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.leagues.session-formats.store', $this->league), [
                'name' => 'Sprint Double', 'practice_duration' => 10, 'qualifying_duration' => '',
                'race_lengths' => '20, 20', 'pitstop_count' => 1, 'fixed_stop_time' => 1, 'tyre_set_count' => 3,
            ])
            ->assertRedirect(route('admin.leagues.session-formats.index', $this->league));

        $format = SessionFormat::where('league_id', $this->league->id)->firstOrFail();
        $this->assertSame([20, 20], $format->race_durations);
        $this->assertNull($format->qualifying_duration);
        $this->assertTrue($format->fixed_stop_time);
        $this->assertSame('P 10 · R 20 + 20 min', $format->summary());

        $this->actingAs($this->manager)
            ->put(route('admin.leagues.session-formats.update', [$this->league, $format]), [
                'name' => 'Sprint Triple', 'race_lengths' => '15,15,15',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame([15, 15, 15], $format->fresh()->race_durations);

        $this->actingAs($this->manager)->get(route('admin.leagues.session-formats.index', $this->league))
            ->assertOk()->assertSee('Sprint Triple');
    }

    public function test_race_lengths_must_be_one_to_four_races_of_whole_minutes(): void
    {
        foreach (['', 'long', '25, 0', '10,10,10,10,10'] as $bad) {
            $this->actingAs($this->manager)
                ->post(route('admin.leagues.session-formats.store', $this->league), ['name' => 'X', 'race_lengths' => $bad])
                ->assertSessionHasErrors('race_lengths');
        }
        $this->assertSame(0, SessionFormat::count());
    }

    public function test_another_leagues_manager_cannot_touch_a_format(): void
    {
        $format = $this->makeFormat($this->league);
        $other = $this->makeLeague('other');
        $otherManager = $this->managerOf($other);

        // Refused either way — hidden by the tenant scope (404) or not their league (403).
        foreach ([$this->league, $other] as $league) {
            $status = $this->actingAs($otherManager)
                ->put(route('admin.leagues.session-formats.update', [$league, $format]), ['name' => 'Hijack', 'race_lengths' => '5'])
                ->status();
            $this->assertContains($status, [403, 404]);
        }

        $this->assertSame('Double Header', $format->fresh()->name);
    }

    public function test_a_round_takes_multiple_race_lengths_and_its_format(): void
    {
        $championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $format = $this->makeFormat($this->league);

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.rounds.create', [$this->league, $championship]))
            ->assertOk()
            ->assertSee('Double Header — P 10 · Q 15 · R 25 + 20 min');

        $this->actingAs($this->manager)
            ->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
                'track' => 'monza', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i'),
                'race_lengths' => '25, 20', 'session_format_id' => $format->id,
            ])
            ->assertSessionHasNoErrors();

        $round = $championship->rounds()->firstOrFail();
        $this->assertSame(25, (int) $round->race_duration);
        $this->assertSame([25, 20], $round->race_durations);
        $this->assertSame(2, $round->raceCount());
        $this->assertSame($format->id, $round->session_format_id);

        // Back to a single race clears the multi-race list.
        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.rounds.update', [$this->league, $championship, $round]), [
                'track' => 'monza', 'scheduled_at' => $round->scheduled_at->timezone('Europe/London')->format('Y-m-d\TH:i'),
                'race_lengths' => '30',
            ])
            ->assertSessionHasNoErrors();
        $this->assertNull($round->fresh()->race_durations);
        $this->assertSame([30], $round->fresh()->raceLengths());
    }

    public function test_a_round_cannot_use_another_leagues_format(): void
    {
        $championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $foreign = $this->makeFormat($this->makeLeague('other'));

        $this->actingAs($this->manager)
            ->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
                'track' => 'monza', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i'),
                'session_format_id' => $foreign->id,
            ])
            ->assertForbidden();
    }
}
