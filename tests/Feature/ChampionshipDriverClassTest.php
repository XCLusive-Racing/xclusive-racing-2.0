<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipDriverClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Driver classes (Pro / Pro-Am / Am): defined on the Format step, assigned by the
// league by hand on the Entries page, scored on the positions within the class,
// and able to set the ACC number banner.
class ChampionshipDriverClassTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

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

    private function makeChampionship(bool $driverClasses = true): Championship
    {
        $settings = ChampionshipSettingsSchema::defaults();
        $settings['format']['driver_classes_enabled'] = $driverClasses;

        return Championship::create([
            'league_id' => $this->league->id, 'name' => 'Pro-Am Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => $settings, 'points_system' => [25, 18, 15, 12],
        ]);
    }

    private function register(Championship $championship, User $user, ?ChampionshipDriverClass $class = null): ChampionshipRegistration
    {
        return ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $user->id, 'driver_class_id' => $class?->id,
        ]);
    }

    private function makeResult(Race $race, User $user, int $position): void
    {
        RaceResult::create([
            'race_id' => $race->id, 'session_type' => 'race', 'user_id' => $user->id,
            'driver_name' => $user->name, 'position' => $position, 'lap_count' => 20,
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);
    }

    public function test_format_step_creates_driver_classes_and_a_rename_keeps_the_entries_in_it(): void
    {
        $championship = $this->makeChampionship(false);

        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.wizard.update', [$this->league, $championship, 'format']), [
                'settings' => ['format' => ['driver_classes_enabled' => 1]],
                'driver_classes_json' => json_encode([
                    ['id' => null, 'name' => 'Pro', 'acc_category' => 2, 'max_entries' => null],
                    ['id' => null, 'name' => 'Pro-Am', 'acc_category' => 0, 'max_entries' => 10],
                ]),
            ])
            ->assertRedirect();

        $championship->refresh();
        $this->assertTrue($championship->usesDriverClasses());
        $this->assertSame(['Pro', 'Pro-Am'], $championship->driverClasses->pluck('name')->all());
        $pro = $championship->driverClasses->first();
        $this->assertSame(2, $pro->acc_category);
        $this->assertNull($pro->max_entries);

        $registration = $this->register($championship, User::factory()->create(), $pro);

        // Rename Pro, drop Pro-Am.
        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.wizard.update', [$this->league, $championship, 'format']), [
                'settings' => ['format' => ['driver_classes_enabled' => 1]],
                'driver_classes_json' => json_encode([
                    ['id' => $pro->id, 'name' => 'Platinum', 'acc_category' => 2, 'max_entries' => null],
                ]),
            ])
            ->assertRedirect();

        $this->assertSame(['Platinum'], $championship->driverClasses()->pluck('name')->all());
        $this->assertSame($pro->id, $registration->fresh()->driver_class_id);
    }

    public function test_league_manager_assigns_a_driver_class_and_a_full_class_is_refused(): void
    {
        $championship = $this->makeChampionship();
        $pro = $championship->driverClasses()->create(['name' => 'Pro', 'max_entries' => 1]);
        $first = $this->register($championship, User::factory()->create());
        $second = $this->register($championship, User::factory()->create());

        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.entries.driver-class', [$this->league, $championship, $first]), ['driver_class_id' => $pro->id])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame($pro->id, $first->fresh()->driver_class_id);

        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.entries.driver-class', [$this->league, $championship, $second]), ['driver_class_id' => $pro->id])
            ->assertSessionHas('error');
        $this->assertNull($second->fresh()->driver_class_id);

        // Re-saving the entry already in the full class is fine, and clearing works.
        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.entries.driver-class', [$this->league, $championship, $first]), ['driver_class_id' => $pro->id])
            ->assertSessionHas('success');
        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.entries.driver-class', [$this->league, $championship, $first]), ['driver_class_id' => ''])
            ->assertSessionHas('success');
        $this->assertNull($first->fresh()->driver_class_id);
    }

    public function test_a_class_of_another_championship_cannot_be_assigned(): void
    {
        $championship = $this->makeChampionship();
        $otherClass = $this->makeChampionship()->driverClasses()->create(['name' => 'Pro']);
        $registration = $this->register($championship, User::factory()->create());

        $this->actingAs($this->manager)
            ->put(route('admin.leagues.championships.entries.driver-class', [$this->league, $championship, $registration]), ['driver_class_id' => $otherClass->id])
            ->assertSessionHasErrors('driver_class_id');

        $this->assertNull($registration->fresh()->driver_class_id);
    }

    public function test_class_standings_score_the_position_within_the_class_next_to_the_overall_standings(): void
    {
        $championship = $this->makeChampionship();
        $pro = $championship->driverClasses()->create(['name' => 'Pro', 'sort_order' => 0]);
        $am = $championship->driverClasses()->create(['name' => 'Am', 'sort_order' => 1]);

        [$pro1, $pro2, $am1] = User::factory()->count(3)->create()->all();
        $unassigned = User::factory()->create();
        $this->register($championship, $pro1, $pro);
        $this->register($championship, $pro2, $pro);
        $this->register($championship, $am1, $am);
        $this->register($championship, $unassigned);

        $race = Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subWeek(),
        ]);
        $this->makeResult($race, $pro1, 1);
        $this->makeResult($race, $unassigned, 2);
        $this->makeResult($race, $pro2, 3);
        $this->makeResult($race, $am1, 4);

        $overall = collect($championship->computeStandings())->pluck('total_points', 'user_id');
        $this->assertEquals(12, $overall[$am1->id]);

        $groups = collect($championship->computeDriverClassStandings())->keyBy(fn ($group) => $group['driver_class']->name);
        $this->assertSame(['Pro', 'Am'], $groups->keys()->all());

        $proStandings = collect($groups['Pro']['standings'])->pluck('total_points', 'user_id');
        $this->assertEquals([$pro1->id => 25, $pro2->id => 18], $proStandings->all());

        // P4 overall, but first of the Am class.
        $amStandings = collect($groups['Am']['standings'])->pluck('total_points', 'user_id');
        $this->assertEquals([$am1->id => 25], $amStandings->all());
    }

    public function test_driver_class_standings_are_empty_when_the_toggle_is_off(): void
    {
        $championship = $this->makeChampionship(false);
        $championship->driverClasses()->create(['name' => 'Pro']);

        $this->assertSame([], $championship->computeDriverClassStandings());
    }

    public function test_entrylist_banner_follows_the_driver_class_and_falls_back_to_the_rating(): void
    {
        $championship = $this->makeChampionship();
        $pro = $championship->driverClasses()->create(['name' => 'Pro', 'acc_category' => 2]);
        $byRating = $championship->driverClasses()->create(['name' => 'Am', 'acc_category' => null]);

        $rookiePro = User::factory()->create(['platform_id' => 'XUID-PRO', 'elo_acc' => 0]);
        $rookieAm = User::factory()->create(['platform_id' => 'XUID-AM', 'elo_acc' => 0]);
        $this->register($championship, $rookiePro, $pro);
        $this->register($championship, $rookieAm, $byRating);

        $race = Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $rookiePro->id]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $rookieAm->id]);

        $categories = collect(app(AccServerConfigService::class)->entryList($race)['entries'])
            ->mapWithKeys(fn ($entry) => [$entry['drivers'][0]['playerID'] => $entry['drivers'][0]['driverCategory']]);

        $this->assertSame(2, $categories['XUID-PRO']); // white banner from the class
        $this->assertSame(0, $categories['XUID-AM']);  // rookie rating: red
    }

    public function test_format_step_entries_page_and_public_page_render_the_driver_classes(): void
    {
        $championship = $this->makeChampionship();
        $pro = $championship->driverClasses()->create(['name' => 'Pro', 'acc_category' => 2, 'max_entries' => 5]);
        $this->register($championship, User::factory()->create(['name' => 'Fast Driver']), $pro);

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.wizard', [$this->league, $championship, 'format']))
            ->assertOk()
            ->assertSee('data-driver-classes-builder', false)
            ->assertSee('value="Pro"', false);

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.entries.index', [$this->league, $championship]))
            ->assertOk()
            ->assertSee('Driver Class')
            ->assertSee('Pro: 1/5');

        $this->get(route('championships.show', $championship))
            ->assertOk()
            ->assertSee('Fast Driver');
    }
}
