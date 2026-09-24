<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09-24: the "Team / Quote" profile field (sent as the ACC entrylist
// teamName for drivers without a RacingTeam) used to be a supporter-only perk. It's now
// open to everyone, still capped at 16 characters.
class ProfileTeamQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_supporter_can_set_their_team_quote(): void
    {
        $user = User::factory()->create(['is_supporter' => false, 'team' => null]);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => 'Night Owls RT'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Night Owls RT', $user->fresh()->team);
    }

    public function test_team_quote_is_capped_at_16_characters(): void
    {
        $user = User::factory()->create(['team' => 'Old']);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => str_repeat('x', 17)])
            ->assertSessionHasErrors('team');

        $this->assertSame('Old', $user->fresh()->team);
    }
}
