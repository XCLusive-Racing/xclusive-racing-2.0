<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipDriverClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A driver class can cover a range of XCL ranks (Pro = Silver and up, Am = Rookie
// to Bronze), so an entry lands in its class at registration instead of by hand.
class ChampionshipDriverClassRankTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    private int $nextCarNumber = 10;

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
    }

    private function makeChampionship(): Championship
    {
        $settings = ChampionshipSettingsSchema::defaults();
        $settings['format']['driver_classes_enabled'] = true;

        return Championship::create([
            'league_id' => $this->league->id, 'name' => 'Sunday League', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => $settings, 'points_system' => [25, 18, 15, 12],
        ]);
    }

    // Sunday League's own split: Silver and up is Pro, Rookie/Bronze is Am.
    private function sundayLeagueClasses(Championship $championship): array
    {
        return [
            'pro' => $championship->driverClasses()->create(['name' => 'Pro', 'min_rank' => 'silver', 'sort_order' => 0]),
            'am' => $championship->driverClasses()->create(['name' => 'Am', 'max_rank' => 'bronze', 'sort_order' => 1]),
        ];
    }

    private function carFields(): array
    {
        return ['car_model' => 'Ferrari 296 GT3 (2023)', 'car_number' => $this->nextCarNumber++];
    }

    private function driverWithElo(int $elo): User
    {
        return User::factory()->create(['elo_acc' => $elo, 'sr_acc' => 9.99]);
    }

    public function test_covers_rank_honours_open_and_closed_bounds(): void
    {
        $proAm = new ChampionshipDriverClass(['min_rank' => 'bronze', 'max_rank' => 'gold']);
        $this->assertFalse($proAm->coversRank('rookie'));
        $this->assertTrue($proAm->coversRank('bronze'));
        $this->assertTrue($proAm->coversRank('gold'));
        $this->assertFalse($proAm->coversRank('platinum'));

        $pro = new ChampionshipDriverClass(['min_rank' => 'silver']);
        $this->assertTrue($pro->coversRank('legend'));
        $this->assertFalse($pro->coversRank('bronze'));

        $manual = new ChampionshipDriverClass(['name' => 'Guest']);
        $this->assertFalse($manual->coversRank('gold'));
        $this->assertNull($manual->rankRangeLabel());
        $this->assertSame('Silver+', $pro->rankRangeLabel());
        $this->assertSame('Bronze – Gold', $proAm->rankRangeLabel());
    }

    public function test_registering_puts_the_driver_in_the_class_matching_their_rank(): void
    {
        $championship = $this->makeChampionship();
        $classes = $this->sundayLeagueClasses($championship);

        $silver = $this->driverWithElo(4000);
        $bronze = $this->driverWithElo(2500);
        $rookie = $this->driverWithElo(500);

        foreach ([$silver, $bronze, $rookie] as $driver) {
            $this->actingAs($driver)->post(route('championships.register', $championship), $this->carFields())->assertSessionHas('success');
        }

        $classOf = fn (User $user) => ChampionshipRegistration::where('user_id', $user->id)->value('driver_class_id');
        $this->assertSame($classes['pro']->id, $classOf($silver));
        $this->assertSame($classes['am']->id, $classOf($bronze));
        $this->assertSame($classes['am']->id, $classOf($rookie));
    }

    public function test_a_full_matching_class_or_no_matching_class_leaves_the_entry_for_the_league(): void
    {
        $championship = $this->makeChampionship();
        $pro = $championship->driverClasses()->create(['name' => 'Pro', 'min_rank' => 'silver', 'max_entries' => 1]);

        $first = $this->driverWithElo(4000);
        $second = $this->driverWithElo(4500);
        $bronze = $this->driverWithElo(2500);

        foreach ([$first, $second, $bronze] as $driver) {
            $this->actingAs($driver)->post(route('championships.register', $championship), $this->carFields())->assertSessionHas('success');
        }

        $this->assertSame($pro->id, ChampionshipRegistration::where('user_id', $first->id)->value('driver_class_id'));
        $this->assertNull(ChampionshipRegistration::where('user_id', $second->id)->value('driver_class_id'));
        $this->assertNull(ChampionshipRegistration::where('user_id', $bronze->id)->value('driver_class_id'));
    }

    public function test_spectators_are_never_put_in_a_class(): void
    {
        $championship = $this->makeChampionship();
        $settings = $championship->settings->toArray();
        $settings['format']['spectator_slots'] = 2;
        $championship->update(['settings' => $settings]);
        $this->sundayLeagueClasses($championship);
        $driver = $this->driverWithElo(4000);

        $this->actingAs($driver)->post(route('championships.register', $championship), ['is_spectator' => 1])->assertSessionHas('success');

        $this->assertNull(ChampionshipRegistration::where('user_id', $driver->id)->value('driver_class_id'));
    }

    public function test_auto_assign_button_places_existing_entries_but_never_moves_a_placed_one(): void
    {
        $championship = $this->makeChampionship();
        $classes = $this->sundayLeagueClasses($championship);

        $unplaced = ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $this->driverWithElo(4000)->id]);
        // A bronze driver the league deliberately put in Pro stays there.
        $placed = ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $this->driverWithElo(2500)->id, 'driver_class_id' => $classes['pro']->id,
        ]);

        $this->actingAs($this->manager)
            ->post(route('admin.leagues.championships.entries.driver-class.auto', [$this->league, $championship]))
            ->assertRedirect()
            ->assertSessionHas('success', '1 entry put in a class.');

        $this->assertSame($classes['pro']->id, $unplaced->fresh()->driver_class_id);
        $this->assertSame($classes['pro']->id, $placed->fresh()->driver_class_id);
    }

    public function test_a_driver_cannot_run_the_auto_assign(): void
    {
        $championship = $this->makeChampionship();

        $this->actingAs($this->driverWithElo(4000))
            ->post(route('admin.leagues.championships.entries.driver-class.auto', [$this->league, $championship]))
            ->assertNotFound(); // the tenant scope hides another league's championship
    }

    public function test_format_step_saves_the_rank_range_lowest_first(): void
    {
        $championship = $this->makeChampionship();

        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.wizard.update', [$this->league, $championship, 'format']), [
                'settings' => ['format' => ['driver_classes_enabled' => 1]],
                'driver_classes_json' => json_encode([
                    ['id' => null, 'name' => 'Pro', 'acc_category' => 2, 'max_entries' => null, 'min_rank' => 'silver', 'max_rank' => null],
                    // Picked the wrong way round — stored as Rookie to Bronze.
                    ['id' => null, 'name' => 'Am', 'acc_category' => 0, 'max_entries' => null, 'min_rank' => 'bronze', 'max_rank' => 'rookie'],
                    ['id' => null, 'name' => 'Guest', 'acc_category' => null, 'max_entries' => null, 'min_rank' => 'nonsense', 'max_rank' => null],
                ]),
            ])
            ->assertRedirect();

        [$pro, $am, $guest] = $championship->driverClasses()->get()->all();
        $this->assertSame(['silver', null], [$pro->min_rank, $pro->max_rank]);
        $this->assertSame(['rookie', 'bronze'], [$am->min_rank, $am->max_rank]);
        $this->assertSame([null, null], [$guest->min_rank, $guest->max_rank]);

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.wizard', [$this->league, $championship, 'format']))
            ->assertOk()
            ->assertSee('From: Silver');
    }
}
