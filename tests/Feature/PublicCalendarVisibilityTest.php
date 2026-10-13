<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-13: hidden and draft events showed in the public calendar. The
// calendar, the Events page and the daily Discord post share Race::scopePubliclyListed().
class PublicCalendarVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function championship(string $status, string $visibility = 'public'): Championship
    {
        return Championship::create([
            'league_id' => $this->league->id, 'name' => ucfirst($status).' '.$visibility, 'game' => 'acc', 'season' => 2026,
            'status' => $status, 'visibility' => $visibility, 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function race(string $title, array $attributes = []): Race
    {
        return Race::create($attributes + [
            'title' => $title, 'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addDays(2),
        ]);
    }

    public function test_draft_rounds_and_hidden_or_draft_championships_stay_off_the_calendar(): void
    {
        $public = $this->championship('registration_open');
        $this->race('Plain Daily Race');
        $this->race('Public Round', ['championship_id' => $public->id, 'round_number' => 1]);
        $this->race('Draft Round', ['championship_id' => $public->id, 'round_number' => 2, 'status' => 'draft']);
        $this->race('Hidden Champ Round', ['championship_id' => $this->championship('registration_open', 'unlisted')->id, 'round_number' => 1]);
        $this->race('Draft Champ Round', ['championship_id' => $this->championship('draft')->id, 'round_number' => 1]);

        $when = now()->addDays(2);
        $calendar = $this->get(route('calendar', ['year' => $when->year, 'month' => $when->month]))->assertOk();
        $events = $this->get(route('events.index'))->assertOk();

        foreach ([$calendar, $events] as $page) {
            $page->assertSee('Plain Daily Race')->assertSee('Public Round')
                ->assertDontSee('Draft Round')->assertDontSee('Hidden Champ Round')->assertDontSee('Draft Champ Round');
        }
    }
}
