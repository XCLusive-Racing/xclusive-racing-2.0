<?php

namespace Tests\Feature;

use App\Models\EventFormat;
use App\Models\Race;
use App\Models\Role;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10: a started event stays on the public Events page for half an hour,
// marked LIVE, then drops off; the admin Standard Races list's "Upcoming" is only what is
// still to come or running now.
class LiveEventsTest extends TestCase
{
    use RefreshDatabase;

    private function race(string $title, $scheduledAt, array $attributes = []): Race
    {
        $format = EventFormat::firstOrCreate(
            ['game' => 'acc', 'name' => 'Sprint Race'],
            ['sort_order' => 2, 'default_event_tag' => 'sprint', 'race1_mins' => 20],
        );

        return Race::create($attributes + [
            'title' => $title, 'track' => 'Monza', 'game' => 'acc', 'status' => 'closed',
            'scheduled_at' => $scheduledAt, 'event_format_id' => $format->id,
            'practice_duration' => 10, 'qualifying_duration' => 10, 'race_duration' => 20,
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

        return new DOMXPath($dom);
    }

    public function test_the_events_page_keeps_a_started_event_for_half_an_hour_marked_live(): void
    {
        $upcoming = $this->race('Upcoming', now()->addHour(), ['status' => 'open']);
        $live = $this->race('Just started', now()->subMinutes(10));
        $gone = $this->race('Started a while ago', now()->subMinutes(31));
        $old = $this->race('Never got results', now()->subDays(3));

        $xpath = $this->xpath($this->get(route('events.index'))->assertOk()->getContent());
        $card = fn (Race $race) => $xpath->query('//div[@data-event-card]//a[@href="'.route('events.show', $race).'"]/ancestor::div[@data-live-from][1]')->item(0);

        $this->assertStringContainsString('xcl-ec2--live', $card($live)->getAttribute('class'));
        $this->assertStringNotContainsString('xcl-ec2--live', $card($upcoming)->getAttribute('class'));
        $this->assertNull($card($gone));
        $this->assertNull($card($old));

        // Still reachable for its specs while it runs.
        $this->get(route('events.show', $live))->assertOk();
    }

    public function test_the_admin_list_counts_only_coming_and_running_races_as_upcoming(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $expected = [
            $this->race('Upcoming', now()->addHour(), ['status' => 'open'])->id => 'upcoming',
            // 40 minutes of sessions: 35 minutes in is still running, 45 is over.
            $this->race('Running', now()->subMinutes(35))->id => 'upcoming',
            $this->race('Over', now()->subMinutes(45))->id => 'past',
            $this->race('Never got results', now()->subDays(3))->id => 'past',
            $this->race('Finished', now()->subMinutes(35), ['status' => 'finished'])->id => 'past',
        ];

        $xpath = $this->xpath($this->actingAs($admin)->get(route('admin.races.index'))->assertOk()->getContent());

        foreach ($expected as $id => $when) {
            $row = $xpath->query('//input[@class="race-checkbox"][@value="'.$id.'"]/ancestor::tr[1]')->item(0);
            $this->assertSame($when, $row->getAttribute('data-when'), 'race '.$id);
        }

        $this->assertSame(1, $xpath->query('//span[contains(@class, "status-live")]')->length);
    }
}
