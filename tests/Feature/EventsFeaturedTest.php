<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\EventFormat;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// The Events page's featured row, per game: Popular Today (most sign-ups in the next 24
// hours), the Weekly Event (Saturday 19:00 UK) and the next Special Event.
class EventsFeaturedTest extends TestCase
{
    use RefreshDatabase;

    private ?EventFormat $format = null;

    protected function setUp(): void
    {
        parent::setUp();
        // Saturday 3 October 2026, noon.
        Carbon::setTestNow(Carbon::parse('2026-10-03 12:00', 'Europe/London'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function championship(): Championship
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);

        return Championship::withoutTenantScope()->create([
            'league_id' => $league->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    // A regular (format-based) race; special() makes a Custom Race instead.
    private function race(string $title, string $when, int $signups = 0, array $attributes = []): Race
    {
        $this->format ??= EventFormat::create([
            'game' => 'acc', 'name' => 'Sprint Race', 'sort_order' => 1, 'default_event_tag' => 'sprint', 'race1_mins' => 20,
        ]);

        $race = Race::create($attributes + [
            'title' => $title, 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            // $when is a time today ("21:00") or a full date-time.
            'scheduled_at' => Carbon::parse(strlen($when) > 5 ? $when : "2026-10-03 {$when}", 'Europe/London'),
            'event_format_id' => $this->format->id,
        ]);
        for ($i = 0; $i < $signups; $i++) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => User::factory()->create()->id]);
        }

        return $race;
    }

    private function special(string $title, string $when, int $signups = 0): Race
    {
        return $this->race($title, $when, $signups, ['event_format_id' => null]);
    }

    /** @return array<string, array<string, ?string>> */
    private function featuredTitles(): array
    {
        $featured = $this->get(route('events.platform', 'acc-console'))->assertOk()->viewData('featured');

        return $featured->map(fn (array $picks) => array_map(fn (?Race $race) => $race?->title, $picks))->all();
    }

    public function test_each_slot_gets_its_own_pick(): void
    {
        $this->race('Already started', '11:00', 50);
        $this->race('Evening big', '21:00', 20);
        $this->race('Late mid', '23:00', 12);
        $this->race('Weekly', '19:00', 40);
        $this->race('Next weekly', '2026-10-10 19:00', 5);
        $this->race('Tomorrow afternoon huge', '2026-10-04 13:00', 99);
        $this->special('Special', '2026-10-25 19:00');
        $this->special('Later special', '2026-11-22 19:00');

        // The weekly event isn't also picked as Popular Today, even with the most sign-ups.
        $this->assertSame(['acc' => [
            'popular' => 'Evening big', 'weekly' => 'Weekly', 'special' => 'Special',
        ]], $this->featuredTitles());
    }

    public function test_the_weekly_event_stays_until_it_is_finished_then_the_next_one_takes_over(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-03 20:00', 'Europe/London'));
        $weekly = $this->race('Weekly', '19:00', 0, ['status' => 'closed']);
        $this->race('Next weekly', '2026-10-10 19:00');

        $this->assertSame('Weekly', $this->featuredTitles()['acc']['weekly']);

        $weekly->update(['status' => 'finished']);
        $this->assertSame('Next weekly', $this->featuredTitles()['acc']['weekly']);
    }

    public function test_only_regular_saturday_7pm_uk_races_count_as_weekly(): void
    {
        $this->race('Saturday 8pm', '20:00');
        $this->race('Sunday 7pm', '2026-10-04 19:00');
        $this->special('Special on the weekly slot', '19:00');

        $this->assertSame(['acc' => [
            'popular' => null, 'weekly' => null, 'special' => 'Special on the weekly slot',
        ]], $this->featuredTitles());
    }

    public function test_championship_rounds_are_not_special_events(): void
    {
        $championship = $this->championship();
        $round = $this->special('Round 1', '2026-10-25 19:00');
        $round->update(['championship_id' => $championship->id]);

        $this->assertFalse($round->fresh()->isSpecialEvent());
        $this->assertSame(0, Race::specialEvents()->count());
    }

    public function test_the_row_shows_each_slots_title_and_is_left_out_when_all_are_empty(): void
    {
        $this->race('Evening', '21:00', 3);
        $this->special('Special', '2026-10-25 19:00');

        $this->get(route('events.platform', 'acc-console'))
            ->assertSee('Popular Today')->assertSee('Special Event')->assertDontSee('Weekly Event');

        Race::query()->delete();
        $this->race('Quiet', '20:00');
        $this->assertSame([], $this->featuredTitles());
        $this->get(route('events.platform', 'acc-console'))->assertDontSee('Popular Today');
    }

    public function test_phone_carousel_buttons_follow_the_slots_with_popular_today_first(): void
    {
        $this->race('Weekly', '19:00', 2);
        $this->race('Evening', '21:00', 3);
        $this->special('Special', '2026-10-25 19:00');

        $dom = new \DOMDocument;
        @$dom->loadHTML($this->get(route('events.platform', 'acc-console'))->getContent());
        $xpath = new \DOMXPath($dom);

        $tabs = array_map(fn ($tab) => trim($tab->textContent), iterator_to_array($xpath->query('//*[@data-featured-tab]')));
        $cards = array_map(fn ($card) => $card->getAttribute('data-featured-card'), iterator_to_array($xpath->query('//*[@data-featured-card]')));

        $this->assertSame(['Popular Today', 'Weekly Event', 'Special Event'], $tabs);
        $this->assertSame(['popular', 'weekly', 'special'], $cards);
        $this->assertSame(1, $xpath->query('//*[@data-featured-next]')->length);
    }

    public function test_admin_special_events_tab_leaves_out_championship_rounds(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        $championship = $this->championship();
        $this->special('Monthly Special', '2026-10-25 19:00');
        $this->special('Cup Round 1', '2026-10-18 19:00')->update(['championship_id' => $championship->id]);

        $this->actingAs($admin)->get(route('admin.races.special'))
            ->assertOk()->assertSee('Monthly Special')->assertDontSee('Cup Round 1');
    }
}
