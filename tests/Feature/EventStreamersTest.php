<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The event page's Watch Live bar: a registered supporter with a stream link on their
// profile adds it to a race with one button (nothing is added automatically); the bar
// shows up to eight and is hidden without any.
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

    private function registered(Race $race, bool $supporter = true, ?string $profileStream = 'https://twitch.tv/xcl', ?string $raceStream = null): User
    {
        $user = User::factory()->create(['is_supporter' => $supporter, 'stream_url' => $profileStream]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id, 'stream_url' => $raceStream]);

        return $user;
    }

    private function raceStream(User $user): ?string
    {
        return RaceRegistration::where('user_id', $user->id)->value('stream_url');
    }

    public function test_a_supporter_adds_and_removes_their_profile_stream_with_the_button(): void
    {
        $race = $this->race();
        $user = $this->registered($race);

        // Having a profile link alone doesn't put them in the bar.
        $this->actingAs($user)->get(route('events.show', $race))->assertOk()
            ->assertSee('+ Add my stream')->assertDontSee('xcl-streamers-bar', false);

        $this->actingAs($user)->put(route('events.stream', $race))->assertSessionHas('success');
        $this->assertSame('https://twitch.tv/xcl', $this->raceStream($user));
        $this->actingAs($user)->get(route('events.show', $race))->assertOk()
            ->assertSee('xcl-streamers-bar', false)->assertSee('Remove');

        $this->actingAs($user)->put(route('events.stream', $race), ['remove' => 1]);
        $this->assertNull($this->raceStream($user));
    }

    public function test_a_supporter_without_a_profile_link_is_sent_to_their_profile_first(): void
    {
        $race = $this->race();
        $user = $this->registered($race, profileStream: null);

        $this->actingAs($user)->get(route('events.show', $race))->assertOk()
            ->assertSee('Add your stream link to')->assertDontSee('+ Add my stream');

        $this->actingAs($user)->put(route('events.stream', $race))->assertSessionHas('error');
        $this->assertNull($this->raceStream($user));
    }

    public function test_a_non_supporter_sees_the_supporter_prompt_at_the_bottom_and_cannot_add(): void
    {
        $race = $this->race();
        $user = $this->registered($race, supporter: false);

        $html = $this->actingAs($user)->get(route('events.show', $race))->assertOk()
            ->assertSee('Become a supporter')->assertDontSee('+ Add my stream')->getContent();
        // Under the UNREGISTER button, at the bottom of the Registration card.
        $this->assertGreaterThan(strpos($html, '>UNREGISTER<'), strpos($html, 'Become a supporter'));

        $this->actingAs($user)->put(route('events.stream', $race));
        $this->assertNull($this->raceStream($user));
    }

    public function test_unregistered_users_cannot_add_a_stream(): void
    {
        $race = $this->race();
        $user = User::factory()->create(['is_supporter' => true, 'stream_url' => 'https://twitch.tv/xcl']);

        $this->actingAs($user)->put(route('events.stream', $race))->assertSessionHas('error');
        $this->assertSame(0, RaceRegistration::count());
    }

    public function test_the_bar_shows_at_most_eight_streamers_and_drops_lapsed_supporters(): void
    {
        $race = $this->race();
        $this->registered($race, raceStream: null);

        $this->get(route('events.show', $race))->assertOk()->assertDontSee('xcl-streamers-bar', false);

        foreach (range(1, 9) as $i) {
            $this->registered($race, raceStream: $i % 2 ? "https://twitch.tv/driver{$i}" : "https://youtu.be/driver{$i}");
        }
        $this->registered($race, supporter: false, raceStream: 'https://twitch.tv/lapsed');

        $html = $this->get(route('events.show', $race))->assertOk()->assertSee('DRIVERS STREAMING:')->getContent();
        $this->assertSame(8, substr_count($html, 'class="xcl-streamer xcl-streamer--'));
        $this->assertStringContainsString('xcl-streamer--youtube', $html);
        $this->assertStringNotContainsString('lapsed', $html);
    }
}
