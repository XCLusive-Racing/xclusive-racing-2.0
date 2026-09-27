<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Live bug (championship 30, 2026-09-27): withdrawing a team car returned a 405 for
// DELETE championships/{id}/register. The car list with its Withdraw forms sat inside
// the "Add Car" register form; a browser drops a nested <form>, so the Withdraw
// button submitted the register form with _method=DELETE.
class ChampionshipWithdrawTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private array $drivers;

    private RacingTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->drivers = User::factory()->count(2)->create()->all();
        $this->team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $this->owner->id]);
        $this->team->members()->attach(collect($this->drivers)->pluck('id'));
    }

    private function makeChampionship(): Championship
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Team Sprint', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => [
                'driver_swaps_enabled' => true, 'team_registration_scope' => 'championship',
                'max_drivers_per_car' => 2, 'max_cars_per_team' => 6,
            ],
        ]);
        $championship->save();

        return $championship;
    }

    private function makeRound(Championship $championship, int $number, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $number,
            'title' => 'Round '.$number, 'track' => 'Monza', 'game' => 'acc',
            'status' => $status, 'scheduled_at' => now()->addWeeks($number),
        ]);
    }

    private function registerCar(Championship $championship): void
    {
        $ids = collect($this->drivers)->pluck('id')->all();

        $this->actingAs($this->owner)->post(route('championships.register', $championship), [
            'racing_team_id' => $this->team->id, 'driver_ids' => $ids,
            'starting_driver_id' => $ids[0], 'car_number' => 7,
        ])->assertSessionHas('success');
    }

    private function withdraw(Championship $championship)
    {
        return $this->actingAs($this->owner)->delete(route('championships.unregister', $championship), [
            'registration_id' => $championship->registrations()->value('id'),
        ]);
    }

    public function test_the_withdraw_form_is_not_nested_in_the_register_form(): void
    {
        $championship = $this->makeChampionship();
        $this->registerCar($championship);

        $html = $this->actingAs($this->owner)->get(route('championships.show', $championship))
            ->assertOk()->assertSee('Add Car')->getContent();

        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $withdraw = $xpath->query('//form[@action="'.route('championships.unregister', $championship).'"]');
        $this->assertSame(1, $withdraw->length);
        $this->assertSame(0, $xpath->query('ancestor::form', $withdraw->item(0))->length);
    }

    public function test_withdrawing_a_car_takes_it_out_of_open_rounds_only(): void
    {
        $championship = $this->makeChampionship();
        $finished = $this->makeRound($championship, 1, 'finished');
        $open = $this->makeRound($championship, 2);
        $this->registerCar($championship);

        $this->assertSame(1, $open->teamEntries()->count());
        $this->assertSame(2, $open->registrations()->count());

        $this->withdraw($championship)->assertSessionHas('success');

        $this->assertSame(0, $championship->registrations()->count());
        $this->assertSame(0, $open->teamEntries()->count());
        $this->assertSame(0, $open->registrations()->count());
        // A round already raced keeps its entry (and so its results).
        $this->assertSame(1, $finished->teamEntries()->count());
    }

    public function test_withdrawing_works_after_leaving_every_round_individually(): void
    {
        $championship = $this->makeChampionship();
        $rounds = [$this->makeRound($championship, 1), $this->makeRound($championship, 2)];
        $this->registerCar($championship);

        foreach ($rounds as $round) {
            $entry = RaceTeamEntry::where('race_id', $round->id)->firstOrFail();
            $this->actingAs($this->owner)->delete(route('events.unregister-team', [$round, $entry]))
                ->assertSessionHas('success');
        }

        $this->withdraw($championship)->assertSessionHas('success');
        $this->assertSame(0, $championship->registrations()->count());

        // Withdrawing again is a no-op, not an error.
        $this->withdraw($championship)->assertSessionHas('success');
    }

    public function test_a_driver_in_the_car_cannot_withdraw_it_and_nor_can_another_team(): void
    {
        $championship = $this->makeChampionship();
        $this->registerCar($championship);
        $registrationId = $championship->registrations()->value('id');

        $otherOwner = User::factory()->create();
        RacingTeam::create(['name' => 'Other', 'tag' => 'OTH', 'owner_id' => $otherOwner->id]);

        foreach ([$this->drivers[1], $otherOwner] as $user) {
            $this->actingAs($user)->delete(route('championships.unregister', $championship), ['registration_id' => $registrationId]);
        }

        $this->assertDatabaseHas('championship_registrations', ['id' => $registrationId]);
    }
}
