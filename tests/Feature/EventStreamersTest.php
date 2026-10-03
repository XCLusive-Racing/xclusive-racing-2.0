<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Supporters can add a Twitch/YouTube link to a race they're registered for; the event
// page shows up to four of them in a streamers bar, and no bar at all without any.
class EventStreamersTest extends TestCase
{
    use RefreshDatabase;

    private function race(): Race
    {
        return Race::create([
            'title' => 'Daily Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    private function registered(Race $race, bool $supporter = true, ?string $stream = null): User
    {
        $user = User::factory()->create(['is_supporter' => $supporter]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id, 'stream_url' => $stream]);

        return $user;
    }

    public function test_a_supporter_can_add_and_remove_a_stream_link(): void
    {
        $race = $this->race();
        $user = $this->registered($race);

        $this->actingAs($user)->put(route('events.stream', $race), ['stream_url' => 'https://www.twitch.tv/xcl'])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://www.twitch.tv/xcl', RaceRegistration::where('user_id', $user->id)->value('stream_url'));

        $this->actingAs($user)->put(route('events.stream', $race), ['stream_url' => '']);
        $this->assertNull(RaceRegistration::where('user_id', $user->id)->value('stream_url'));
    }

    public function test_only_twitch_or_youtube_links_are_accepted(): void
    {
        $race = $this->race();
        $user = $this->registered($race);

        foreach (['https://example.com/live', 'javascript:alert(1)', 'twitch.tv/xcl'] as $bad) {
            $this->actingAs($user)->put(route('events.stream', $race), ['stream_url' => $bad])
                ->assertSessionHasErrors('stream_url');
        }
        $this->assertNull(RaceRegistration::where('user_id', $user->id)->value('stream_url'));
    }

    public function test_non_supporters_and_unregistered_users_cannot_add_a_link(): void
    {
        $race = $this->race();
        $nonSupporter = $this->registered($race, supporter: false);
        $unregistered = User::factory()->create(['is_supporter' => true]);

        $this->actingAs($nonSupporter)->put(route('events.stream', $race), ['stream_url' => 'https://youtube.com/@xcl']);
        $this->actingAs($unregistered)->put(route('events.stream', $race), ['stream_url' => 'https://youtube.com/@xcl']);

        $this->assertSame(0, RaceRegistration::whereNotNull('stream_url')->count());
    }

    public function test_the_bar_shows_at_most_four_streamers_and_is_hidden_without_any(): void
    {
        $race = $this->race();
        $this->registered($race);

        $this->get(route('events.show', $race))->assertOk()->assertDontSee('xcl-streamers-bar', false);

        foreach (range(1, 5) as $i) {
            $this->registered($race, stream: $i % 2 ? "https://twitch.tv/driver{$i}" : "https://youtu.be/driver{$i}");
        }

        $html = $this->get(route('events.show', $race))->assertOk()->assertSee('WATCH LIVE')->getContent();
        $this->assertSame(4, substr_count($html, 'class="xcl-streamer xcl-streamer--'));
        $this->assertStringContainsString('xcl-streamer--youtube', $html);
    }

    public function test_registered_drivers_see_the_stream_form_or_the_supporter_prompt(): void
    {
        $race = $this->race();

        $this->actingAs($this->registered($race))->get(route('events.show', $race))
            ->assertOk()->assertSee('Streaming this race?')->assertSee('name="stream_url"', false);

        $this->actingAs($this->registered($race, supporter: false))->get(route('events.show', $race))
            ->assertOk()->assertSee('Become a supporter')->assertDontSee('name="stream_url"', false);
    }
}
