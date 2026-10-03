<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// The Events page's "Popular Today" row: per game, the three events still to start
// today with the most sign-ups, shown in start-time order.
class EventsPopularTodayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function race(string $title, string $time, int $signups, string $game = 'acc'): Race
    {
        $race = Race::create([
            'title' => $title, 'track' => 'Monza', 'game' => $game, 'status' => 'open',
            'scheduled_at' => Carbon::parse("2026-10-03 {$time}", 'Europe/London'),
        ]);
        for ($i = 0; $i < $signups; $i++) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => User::factory()->create()->id]);
        }

        return $race;
    }

    /** @return array<string, list<string>> */
    private function popularTitles(): array
    {
        $popular = $this->get(route('events.platform', 'acc-console'))->assertOk()->viewData('popularToday');

        return $popular->map(fn ($races) => $races->pluck('title')->all())->all();
    }

    public function test_the_three_most_signed_up_events_today_are_shown_in_time_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-03 12:00', 'Europe/London'));

        $this->race('Already started', '11:00', 50);
        $this->race('Evening big', '21:00', 20);
        $this->race('Afternoon mid', '15:00', 10);
        $this->race('Afternoon small', '14:00', 2);
        $this->race('Late mid', '23:00', 12);
        $this->race('Empty', '16:00', 0);
        $this->race('LMU only', '18:00', 30, 'lmu');
        Race::create([
            'title' => 'Tomorrow huge', 'track' => 'Spa', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => Carbon::parse('2026-10-04 13:00', 'Europe/London'),
        ]);

        $this->assertSame([
            'acc' => ['Afternoon mid', 'Evening big', 'Late mid'],
            'lmu' => ['LMU only'],
        ], $this->popularTitles());
    }

    public function test_phones_show_the_top_event_portrait_tablets_the_top_two_and_desktop_all_three(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-03 12:00', 'Europe/London'));
        $this->race('Afternoon', '15:00', 5);
        $this->race('Evening', '20:00', 9);
        $this->race('Late', '22:00', 9);

        $dom = new \DOMDocument;
        @$dom->loadHTML($this->get(route('events.platform', 'acc-console'))->getContent());
        $xpath = new \DOMXPath($dom);

        // Each card (in start-time order) -> the classes that decide where it shows.
        $classes = [];
        foreach ($xpath->query('//*[@data-popular-card]') as $card) {
            $link = $xpath->query('.//a[contains(@class,"xcl-see-event-btn")]/@href', $card)->item(0)->nodeValue;
            $classes[Race::find((int) basename($link))?->title ?? $link] = trim(preg_replace('/\s+/', ' ', $card->getAttribute('class')));
        }

        // Tied at 9 sign-ups: the earlier event (Evening) ranks first.
        $this->assertSame([
            'Afternoon' => 'col d-none d-lg-block',
            'Evening' => 'col',
            'Late' => 'col d-none d-md-block',
        ], $classes);
    }

    public function test_no_sign_ups_today_means_no_popular_row(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-03 12:00', 'Europe/London'));
        $this->race('Quiet', '20:00', 0);

        $this->assertSame([], $this->popularTitles());
        $this->get(route('events.platform', 'acc-console'))->assertDontSee('Popular Today');
    }
}
