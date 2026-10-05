<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceClass;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: filler drivers (random gamertags on the rating list) fill event
// grids — one per 4 real sign-ups (4 → 5, 8 → 10, 12 → 15, 16 → 20) — and drop out as
// the grid fills, so a real driver can always sign up and drive straight away. They're
// display-only and never become real registrations.
class GridFillerTest extends TestCase
{
    use RefreshDatabase;

    private function makeRace(array $attributes = []): Race
    {
        return Race::create(array_merge([
            'title' => 'Daily Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addDay(), 'max_drivers' => 30,
        ], $attributes));
    }

    private function registerReal(Race $race, int $count): void
    {
        foreach (User::factory()->count($count)->create() as $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }
    }

    private function seedFillers(): void
    {
        $this->artisan('fillers:seed')->assertSuccessful();
    }

    public function test_one_filler_per_four_real_drivers(): void
    {
        foreach ([0 => 0, 3 => 3, 4 => 5, 8 => 10, 12 => 15, 16 => 20] as $real => $shown) {
            $this->assertSame($shown, $real + Race::fillerCountFor($real, null), "{$real} real drivers");
        }
    }

    // User-directed 2026-10: fillers always leave 5 spots free, so they start dropping out
    // at 45 of 50 (30 of 35) instead of only once the grid is full.
    public function test_fillers_always_leave_five_spots_free(): void
    {
        // 50 grid: 36 real + 9 fillers = 45. The next real sign-up drops one filler.
        $this->assertSame(9, Race::fillerCountFor(36, 50 - 36));
        $this->assertSame(8, Race::fillerCountFor(37, 50 - 37));
        $this->assertSame(1, Race::fillerCountFor(44, 50 - 44));
        $this->assertSame(0, Race::fillerCountFor(45, 50 - 45));

        // 35 grid: 24 real + 6 fillers = 30, then one filler less per new sign-up.
        $this->assertSame(6, Race::fillerCountFor(24, 35 - 24));
        $this->assertSame(5, Race::fillerCountFor(25, 35 - 25));

        // Full grid (a waiting list starts here): no fillers at all.
        $this->assertSame(0, Race::fillerCountFor(50, 0));
    }

    public function test_the_signup_counter_stays_five_below_the_cap_while_fillers_drop_out(): void
    {
        $race = $this->makeRace(['max_drivers' => 50]);

        $this->registerReal($race, 36);
        $this->assertSame(45, $race->loadCount('registrations')->displayedSignupCount());

        $this->registerReal($race, 1);
        $this->assertSame(45, $race->loadCount('registrations')->displayedSignupCount());
    }

    public function test_event_page_shows_fillers_but_they_are_never_registrations(): void
    {
        $this->seedFillers();
        $race = $this->makeRace();
        $this->registerReal($race, 8);

        $response = $this->get(route('events.show', $race))->assertOk();

        $shown = collect(config('fillers.gamertags'))->filter(fn ($name) => str_contains($response->getContent(), e($name)));
        $this->assertCount(2, $shown);
        $this->assertSame(8, $race->registrations()->count());
        $this->assertSame(10, $race->loadCount('registrations')->displayedSignupCount());
    }

    public function test_the_same_fillers_show_on_every_reload(): void
    {
        $this->seedFillers();
        $race = $this->makeRace();

        $first = $race->fillerRegistrations(12, null)->map(fn ($r) => $r->user->id)->all();
        $second = $race->fillerRegistrations(12, null)->map(fn ($r) => $r->user->id)->all();

        $this->assertCount(3, $first);
        $this->assertSame($first, $second);
    }

    public function test_no_fillers_on_championship_endurance_or_past_races(): void
    {
        $this->seedFillers();

        foreach ([['is_endurance' => true], ['is_championship' => true], ['scheduled_at' => now()->subHour()]] as $attributes) {
            $race = $this->makeRace($attributes);
            $this->assertCount(0, $race->fillerRegistrations(8, null));
        }
    }

    // User-reported 2026-09: Rookie fillers showed up in a multiclass race's Bronze+ GT3
    // class. Fillers follow the same sign-up rules as real drivers.
    public function test_fillers_only_join_classes_and_races_they_could_sign_up_for(): void
    {
        foreach ([1200, 1500, 1800, 2100, 2600, 3200] as $i => $elo) {
            $filler = User::factory()->create(['elo_acc' => $elo, 'sr_acc' => 5.0]);
            $filler->forceFill(['is_filler' => true, 'name' => 'Filler'.$i])->save();
        }

        $race = $this->makeRace(['is_multiclass' => true, 'max_drivers' => null]);
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'min_rating' => 'bronze']);
        $gt4 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT4']);

        $gt3Fillers = $race->fillerRegistrations(40, null, $gt3);
        $this->assertCount(3, $gt3Fillers);
        $this->assertTrue($gt3Fillers->every(fn ($r) => $r->user->elo_acc >= 2000));

        // The GT4 box takes the rest, without repeating a GT3 name.
        $used = $gt3Fillers->map(fn ($r) => $r->user->id)->all();
        $gt4Fillers = $race->fillerRegistrations(40, null, $gt4, $used);
        $this->assertCount(3, $gt4Fillers);
        $this->assertEmpty(array_intersect($used, $gt4Fillers->map(fn ($r) => $r->user->id)->all()));

        $rookieRace = $this->makeRace(['max_rating' => 'rookie']);
        $this->assertTrue($rookieRace->fillerRegistrations(40, null)->every(fn ($r) => $r->user->elo_acc < 2000));

        $srRace = $this->makeRace(['sr_requirement' => '6']);
        $this->assertCount(0, $srRace->fillerRegistrations(40, null));
    }

    public function test_fillers_are_on_the_rating_list(): void
    {
        $this->seedFillers();
        $filler = User::where('is_filler', true)->first();

        $this->get(route('drivers.index', ['q' => $filler->name]))
            ->assertOk()
            ->assertSee($filler->name);
    }

    public function test_seeded_ratings_stay_between_1000_and_4000_and_reseeding_adds_nothing(): void
    {
        $this->seedFillers();
        $this->seedFillers();

        $fillers = User::where('is_filler', true)->get();
        $this->assertCount(count(config('fillers.gamertags')), $fillers);
        foreach ($fillers as $filler) {
            $this->assertGreaterThanOrEqual(1000, $filler->elo_acc);
            $this->assertLessThanOrEqual(4000, $filler->elo_acc);
            $this->assertNull($filler->platform_id);
        }
    }

    // Bug 2026-10-05 (race #549): the Events-page counter said 35 (28 real + 7 fillers worked
    // out for the whole race), but the class boxes showed GT3 13 + GT4 17 = 30 — GT4 (17 of
    // its 20) had no room for fillers within its own cap. The counter now adds up exactly
    // the fillers the class boxes show.
    public function test_a_multiclass_counter_adds_up_to_its_class_boxes(): void
    {
        $this->seedFillers();
        $race = $this->makeRace(['title' => 'Multiclass', 'max_drivers' => 40, 'is_multiclass' => true]);
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'car_class' => 'GT3', 'sort_order' => 0, 'max_drivers' => 20]);
        $gt4 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT4', 'car_class' => 'GT4', 'sort_order' => 1, 'max_drivers' => 20]);
        foreach ([[$gt3, 11], [$gt4, 17]] as [$cls, $count]) {
            foreach (User::factory()->count($count)->create() as $user) {
                RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id, 'race_class_id' => $cls->id]);
            }
        }

        $html = $this->get(route('events.show', $race))->assertOk()->getContent();
        preg_match_all('#<h3 class="xcl-event-card__heading">\s*(GT3|GT4)\s*<span[^>]*>\s*(\d+)/20#', $html, $m);
        $boxes = array_combine($m[1], array_map('intval', $m[2]));

        $this->assertSame(['GT3' => 13, 'GT4' => 17], $boxes);

        $counted = Race::withCount('registrations')->with('raceClasses')->find($race->id)->displayedSignupCount();
        $this->assertSame(array_sum($boxes), $counted);
        $this->get(route('events.platform', 'acc-console'))->assertOk()->assertSee('30 / 40');
    }

    // 2026-10: a filler named with a country code at the end gets that country's flag.
    public function test_a_country_code_in_the_name_sets_the_fillers_country(): void
    {
        $this->seedFillers();

        $countries = User::where('is_filler', true)->whereIn('name', ['JoseG_ES', 'fallutNL', 'SjevsjamGB', 'hisname_DK'])->pluck('country', 'name')->all();

        $this->assertSame(['JoseG_ES' => 'ES', 'fallutNL' => 'NL', 'SjevsjamGB' => 'GB', 'hisname_DK' => 'DK'], array_merge(['JoseG_ES' => null, 'fallutNL' => null, 'SjevsjamGB' => null, 'hisname_DK' => null], $countries));
        $this->assertSame(100, User::where('is_filler', true)->count());
    }
}
