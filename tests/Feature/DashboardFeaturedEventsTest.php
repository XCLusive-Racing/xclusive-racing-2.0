<?php

namespace Tests\Feature;

use App\Models\EventFormat;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\DashboardFeaturedEvents;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// User-directed 2026-10: the dashboard shows three featured events between its top columns
// and the XCLusive Team Events strip — the most popular event of the next 24 hours (left),
// the weekly event (middle) and the monthly (special) event (right).
class DashboardFeaturedEventsTest extends TestCase
{
    use RefreshDatabase;

    private ?EventFormat $format = null;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-06 11:00:00'); // a Tuesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function race(string $title, string $time, int $signups = 0, array $attributes = []): Race
    {
        $this->format ??= EventFormat::create([
            'game' => 'acc', 'name' => 'Sprint Race', 'sort_order' => 1, 'default_event_tag' => 'sprint', 'race1_mins' => 20,
        ]);

        $race = Race::create($attributes + [
            'title' => $title, 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => Carbon::parse($time), 'max_drivers' => 30, 'event_format_id' => $this->format->id,
        ]);
        foreach (User::factory()->count($signups)->create() as $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        return $race;
    }

    private function titles(): array
    {
        return array_map(fn (?Race $race) => $race?->title, app(DashboardFeaturedEvents::class)->picks());
    }

    public function test_it_picks_the_popular_weekly_and_monthly_event(): void
    {
        $this->race('Quiet', '2026-10-06 12:00', 1);
        $this->race('Busy', '2026-10-06 17:00', 6, ['game' => 'lmu']);
        $this->race('Busier but tomorrow night', '2026-10-07 20:00', 20);
        $this->race('Weekly', '2026-10-10 18:00', 3);                 // Saturday 19:00 BST
        $this->race('Next weekly', '2026-10-17 18:00');
        $this->race('Saturday, other time', '2026-10-10 17:00');
        $this->race('Monthly', '2026-10-25 19:00', 0, ['event_format_id' => null]);
        $this->race('Later monthly', '2026-11-22 19:00', 0, ['event_format_id' => null]);

        $this->assertSame(['popular' => 'Busy', 'weekly' => 'Weekly', 'monthly' => 'Monthly'], $this->titles());
    }

    public function test_the_weekly_event_is_not_also_the_popular_one_and_finished_events_move_on(): void
    {
        Carbon::setTestNow('2026-10-10 11:00:00');
        $weekly = $this->race('Weekly', '2026-10-10 18:00', 20);
        $this->race('Next weekly', '2026-10-17 18:00');
        $this->race('Sprint', '2026-10-10 14:00', 2);

        $this->assertSame(['popular' => 'Sprint', 'weekly' => 'Weekly', 'monthly' => null], $this->titles());

        $weekly->update(['status' => 'finished']);
        $this->assertSame('Next weekly', $this->titles()['weekly']);
    }

    public function test_the_dashboard_shows_the_three_slots_in_order_above_the_team_events(): void
    {
        $this->race('Busy', '2026-10-06 17:00', 6);
        $weekly = $this->race('Weekly', '2026-10-10 18:00');

        $html = $this->get(route('home'))->assertOk()->getContent();
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $xpath = new DOMXPath($dom);

        $slots = iterator_to_array($xpath->query('//*[@data-sb-featured]'));
        $this->assertSame(['popular', 'weekly', 'monthly'], array_map(fn ($slot) => $slot->getAttribute('data-sb-featured'), $slots));

        // Weekly links to its event; the empty monthly slot says so.
        $this->assertSame(1, $xpath->query('.//a[@href="'.route('events.show', $weekly).'"]', $slots[1])->length);
        $this->assertStringContainsString('No monthly event scheduled', $slots[2]->textContent);

        $this->assertLessThan(strpos($html, 'XCLUSIVE TEAM'), strpos($html, 'data-sb-featured'));
    }
}
