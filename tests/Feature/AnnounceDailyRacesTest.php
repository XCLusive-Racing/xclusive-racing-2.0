<?php

namespace Tests\Feature;

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
// only the 3 most popular races plus any other with more than 8 sign-ups.
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

    private function race(string $title, string $track, string $time, int $signups, string $tag = 'sprint'): Race
    {
        $race = Race::create([
            'title' => $title, 'track' => $track, 'game' => 'acc', 'status' => 'open', 'event_tag' => $tag,
            'scheduled_at' => Carbon::parse($time), 'max_drivers' => 30,
        ]);
        foreach (User::factory()->count($signups)->create() as $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        return $race;
    }

    /** @return array{0: string, 1: list<string>} description and field names of the posted embed */
    private function posted(): array
    {
        $this->artisan('races:announce-daily')->assertSuccessful();

        $embed = null;
        Http::assertSent(function (Request $r) use (&$embed) {
            $embed = $r['embeds'][0];

            return true;
        });

        return [$embed['description'], array_column($embed['fields'], 'name')];
    }

    public function test_only_the_three_most_popular_races_and_busy_ones_are_announced(): void
    {
        $this->race('Daily Race', 'Imola', '2026-10-06 12:00', 2, 'daily');
        $this->race('Super Sprint', 'Brands Hatch', '2026-10-06 14:00', 5);
        $this->race('Sprint Race', 'Zolder', '2026-10-06 17:00', 12);
        $this->race('Full Race', 'Nordschleife', '2026-10-06 18:00', 7);
        $this->race('Daily Race', 'Spa', '2026-10-07 00:00', 4, 'daily');
        $this->race('Full Race', 'Barcelona', '2026-10-07 02:00', 9);
        $this->race('Sprint Race', 'Zandvoort', '2026-10-07 04:00', 0);

        [$description, $fields] = $this->posted();

        // Top 3 (12, 9, 7) plus nobody else above 8 here — in start-time order.
        $this->assertSame([
            '🏎️ Sprint Race — Zolder',
            '🏎️ Full Race — Nordschleife',
            '🏎️ Full Race — Barcelona',
        ], $fields);
        $this->assertStringContainsString('7 races in the next 24 hours.', $description);
        $this->assertStringContainsString('Daily Race: **Imola · Spa**', $description);
        $this->assertStringContainsString('Most popular today', $description);
    }

    public function test_every_race_with_more_than_eight_sign_ups_is_announced_too(): void
    {
        foreach (['Monza' => 15, 'Spa' => 14, 'Zolder' => 13, 'Imola' => 10, 'Misano' => 9, 'Kyalami' => 8] as $track => $signups) {
            $this->race('Sprint Race', $track, '2026-10-06 '.(12 + $signups - 8).':00', $signups);
        }

        [, $fields] = $this->posted();

        $this->assertCount(5, $fields); // 15, 14, 13 (top 3) + 10 and 9; not the 8
        $this->assertNotContains('🏎️ Sprint Race — Kyalami', $fields);
    }

    public function test_without_any_sign_ups_the_next_three_races_are_shown(): void
    {
        $this->race('Daily Race', 'Imola', '2026-10-06 12:00', 0, 'daily');
        $this->race('Super Sprint', 'Brands Hatch', '2026-10-06 14:00', 0);
        $this->race('Sprint Race', 'Zolder', '2026-10-06 17:00', 0);
        $this->race('Full Race', 'Nordschleife', '2026-10-06 18:00', 0);

        [$description, $fields] = $this->posted();

        $this->assertSame(['🏎️ Daily Race — Imola', '🏎️ Super Sprint — Brands Hatch', '🏎️ Sprint Race — Zolder'], $fields);
        $this->assertStringContainsString('Next up', $description);
    }
}
