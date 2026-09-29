<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueRoundType;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A round has a type (Standard / Endurance / Sprint); "+ Add type…" adds a
// league's own type, which stays in that league's dropdown from then on.
class RoundTypeTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    private Championship $championship;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $this->manager->id, 'role' => 'manager']);
        $this->manager->syncLeagueRoleFlags();
        $this->manager->refresh();

        $this->championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function addRound(array $data, int $weeks = 1)
    {
        return $this->actingAs($this->manager)->post(route('admin.leagues.championships.rounds.store', [$this->league, $this->championship]), $data + [
            'track' => 'Monza', 'scheduled_at' => now()->addWeeks($weeks)->startOfHour()->format('Y-m-d\TH:i'),
        ]);
    }

    public function test_a_round_gets_a_built_in_type(): void
    {
        $this->addRound(['round_type' => 'Endurance'])->assertSessionHasNoErrors();

        $this->assertSame('Endurance', $this->championship->rounds()->sole()->round_type);
    }

    public function test_a_new_type_is_saved_for_the_league_and_offered_from_then_on(): void
    {
        $this->addRound(['round_type' => Race::NEW_ROUND_TYPE, 'round_type_new' => 'Night Race'])->assertSessionHasNoErrors();

        $this->assertSame('Night Race', $this->championship->rounds()->sole()->round_type);
        $this->assertSame(['Night Race'], $this->league->roundTypes()->pluck('name')->all());

        // Same name again (other case) reuses it rather than adding a second one.
        $this->addRound(['round_type' => Race::NEW_ROUND_TYPE, 'round_type_new' => 'night race'], 2)->assertSessionHasNoErrors();
        $this->assertSame(1, LeagueRoundType::count());
        $this->assertSame(['Night Race', 'Night Race'], $this->championship->rounds()->orderBy('id')->pluck('round_type')->all());

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.rounds.create', [$this->league, $this->championship]))
            ->assertOk()
            ->assertSee('<option value="Night Race"', false);
    }

    public function test_another_leagues_type_or_an_empty_new_name_is_refused(): void
    {
        $other = League::create([
            'name' => 'Other', 'slug' => 'other',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $other->roundTypes()->create(['name' => 'Secret Type']);

        $this->addRound(['round_type' => 'Secret Type'])->assertSessionHasErrors();
        $this->addRound(['round_type' => Race::NEW_ROUND_TYPE, 'round_type_new' => ' '])->assertSessionHasErrors();

        $this->assertSame(0, $this->championship->rounds()->count());
    }

    public function test_the_type_shows_on_the_championship_and_round_pages(): void
    {
        $this->addRound(['round_type' => 'Sprint'])->assertSessionHasNoErrors();
        $round = $this->championship->rounds()->sole();
        $round->update(['status' => 'open']);

        $this->get(route('championships.show', $this->championship))->assertOk()->assertSee('Sprint');
        $this->get(route('events.show', $round))->assertOk()->assertSee('Sprint');
    }
}
