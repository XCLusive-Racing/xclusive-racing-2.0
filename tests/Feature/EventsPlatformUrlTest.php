<?php

namespace Tests\Feature;

use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: each game on /events gets its own URL, so going back from an
// event page lands on that game's event list instead of the platform picker.
class EventsPlatformUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_platform_slug_opens_the_events_page_on_that_game(): void
    {
        foreach (Race::PLATFORM_SLUGS as $game => $slug) {
            $this->get('/events/'.$slug)
                ->assertOk()
                ->assertSee('data-initial-platform="'.$game.'"', false);
        }
    }

    public function test_plain_events_page_opens_on_the_platform_picker(): void
    {
        $this->get('/events')
            ->assertOk()
            ->assertSee('data-initial-platform=""', false);
    }

    public function test_an_unknown_slug_is_not_treated_as_a_platform(): void
    {
        $this->get('/events/not-a-game')->assertNotFound();
    }
}
