<?php

namespace Tests\Feature;

use App\Models\EventFormat;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// User-directed 2026-10-06: the daily Discord announcement listed every race of the next 24
// hours and got far too long. Now: a line with the race count and the Daily Race tracks, then
// only the most popular races. 2026-10-12: one most popular race plus any other with more
// than 10 sign-ups, and below that the next Weekly Event and Monthly (special) Event.
class AnnounceDailyRacesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.discord.announcer_webhook' => 'https://discord.test/webhook', 'services.discord.xcl_member_role_id' => '123']);
        Carbon::setTestNow('2026-10-06 11:00:00');
        Http::fake(['*' => Http::response(null, 204)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private ?EventFormat $format = null;

    // A regular (format-based) race; $attributes = ['event_format_id' => null] makes it a
    // Custom Race, i.e. a special event.
    private function race(string $title, string $track, string $time, int $signups, string $tag = 'sprint', array $attributes = []): Race
    {
        $this->format ??= EventFormat::create([
            'game' => 'acc', 'name' => 'Sprint Race', 'sort_order' => 1, 'default_event_tag' => 'sprint', 'race1_mins' => 20,
        ]);

        $race = Race::create($attributes + [
            'title' => $title, 'track' => $track, 'game' => 'acc', 'status' => 'open', 'event_tag' => $tag,
            'scheduled_at' => Carbon::parse($time), 'max_drivers' => 30, 'event_format_id' => $this->format->id,
        ]);
        foreach (User::factory()->count($signups)->create() as $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        return $race;
    }

    /** @return array{0: string, 1: list<string>, 2: list<string>} description, field names and field values of the posted embed */
    private function posted(): array
    {
        $this->artisan('races:announce-daily')->assertSuccessful();

        $embed = null;
        Http::assertSent(function (Request $r) use (&$embed) {
            $embed = $r['embeds'][0];

            return true;
        });

        return [$embed['description'], array_column($embed['fields'], 'name'), array_column($embed['fields'], 'value')];
    }

    // UK time with the zone that's actually in effect: BST in summer, GMT in winter.
    public function test_start_times_follow_uk_summer_and_winter_time(): void
    {
        $this->race('Sprint Race', 'Zolder', '2026-10-06 17:00', 1);
        [, , $values] = $this->posted();
        $this->assertStringContainsString('🕐 6:00PM BST', $values[0]);

        Carbon::setTestNow('2026-11-02 11:00:00');
        Http::fake(['*' => Http::response(null, 204)]);
        $this->race('Sprint Race', 'Spa', '2026-11-02 17:00', 1);
        $this->artisan('races:announce-daily', ['--force' => true])->assertSuccessful();
        Http::assertSent(fn (Request $r) => str_contains($r['embeds'][0]['fields'][0]['value'] ?? '', '🕐 5:00PM GMT'));
    }

    public function test_only_the_most_popular_race_and_busy_ones_are_announced(): void
    {
        $this->race('Daily Race', 'Imola', '2026-10-06 12:00', 2, 'daily');
        $this->race('Super Sprint', 'Brands Hatch', '2026-10-06 14:00', 5);
        $this->race('Sprint Race', 'Zolder', '2026-10-06 17:00', 9);
        $this->race('Full Race', 'Nordschleife', '2026-10-06 18:00', 7);
        $this->race('Daily Race', 'Spa', '2026-10-07 00:00', 4, 'daily');
        $this->race('Sprint Race', 'Zandvoort', '2026-10-07 04:00', 0);

        [$description, $fields] = $this->posted();

        // The one with the most sign-ups; nobody above 10 here.
        $this->assertSame(['🏎️ Sprint Race — Zolder'], $fields);
        $this->assertStringContainsString('6 races in the next 24 hours.', $description);
        $this->assertStringContainsString('Daily Race: **Imola · Spa**', $description);
        $this->assertStringContainsString('Most popular today', $description);
    }

    public function test_every_race_with_more_than_ten_sign_ups_is_announced_too(): void
    {
        foreach (['Monza' => 15, 'Spa' => 12, 'Zolder' => 11, 'Imola' => 10, 'Misano' => 9] as $track => $signups) {
            $this->race('Sprint Race', $track, '2026-10-06 '.(3 + $signups).':00', $signups);
        }

        [, $fields] = $this->posted();

        // 15 (most popular) + 12 and 11; not the 10 — in start-time order.
        $this->assertSame(['🏎️ Sprint Race — Zolder', '🏎️ Sprint Race — Spa', '🏎️ Sprint Race — Monza'], $fields);
    }

    public function test_without_any_sign_ups_the_next_race_is_shown(): void
    {
        $this->race('Daily Race', 'Imola', '2026-10-06 12:00', 0, 'daily');
        $this->race('Super Sprint', 'Brands Hatch', '2026-10-06 14:00', 0);

        [$description, $fields] = $this->posted();

        $this->assertSame(['🏎️ Daily Race — Imola'], $fields);
        $this->assertStringContainsString('Next up', $description);
    }

    // The next Weekly Event (Saturday 19:00 UK) and Monthly (special) Event are listed under
    // the popular races with their date, also when they aren't in the next 24 hours.
    public function test_the_weekly_and_monthly_event_are_listed_below(): void
    {
        $this->race('Sprint Race', 'Zolder', '2026-10-06 17:00', 3);
        $this->race('Weekly Race', 'Monza', '2026-10-10 18:00', 20);          // Saturday 19:00 BST
        $this->race('Weekly Race', 'Spa', '2026-10-17 18:00', 4);             // the week after
        $this->race('Monthly Special', 'Mount Panorama', '2026-10-25 19:00', 6, 'endurance', ['event_format_id' => null]);
        $this->race('Later Special', 'Suzuka', '2026-11-08 19:00', 0, 'endurance', ['event_format_id' => null]);

        [$description, $fields, $values] = $this->posted();

        $this->assertSame([
            '🏎️ Sprint Race — Zolder',
            '📆 Weekly Event: Weekly Race — Monza',
            '🏆 Monthly Event: Monthly Special — Mount Panorama',
        ], $fields);
        $this->assertStringContainsString('🗓️ Sat 10 Oct · 🕐 7:00PM BST', $values[1]);
        $this->assertStringContainsString('🗓️ Sun 25 Oct · 🕐 7:00PM GMT', $values[2]);
        $this->assertStringContainsString('1 race in the next 24 hours.', $description);
    }

    // On its own day the weekly event keeps its own line instead of being the popular pick.
    public function test_the_weekly_event_is_not_also_the_most_popular_race(): void
    {
        Carbon::setTestNow('2026-10-10 11:00:00');
        $this->race('Sprint Race', 'Zolder', '2026-10-10 14:00', 3);
        $this->race('Weekly Race', 'Monza', '2026-10-10 18:00', 20);

        [, $fields] = $this->posted();

        $this->assertSame(['🏎️ Sprint Race — Zolder', '📆 Weekly Event: Weekly Race — Monza'], $fields);
    }
}
