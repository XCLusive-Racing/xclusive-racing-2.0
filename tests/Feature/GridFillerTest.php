<?php

namespace Tests\Feature;

use App\Models\Race;
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

    public function test_fillers_never_take_more_than_the_free_spots(): void
    {
        // 28 real on a 30 grid: 7 fillers wanted, only 2 spots left.
        $this->assertSame(2, Race::fillerCountFor(28, 2));
        // Full grid (a waiting list starts here): no fillers at all.
        $this->assertSame(0, Race::fillerCountFor(30, 0));
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
}
