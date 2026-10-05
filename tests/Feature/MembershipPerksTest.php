<?php

namespace Tests\Feature;

use App\Models\ConnectedAccount;
use App\Models\Membership;
use App\Models\RacingTeam;
use App\Models\RacingTeamInvitation;
use App\Models\User;
use App\Services\DiscordRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// What the XCL Supporter membership unlocks besides the page perks: unlimited My Team
// drivers (6 without it) and the Discord supporter role.
class MembershipPerksTest extends TestCase
{
    use RefreshDatabase;

    private function supporter(User $user, string $billing = 'monthly'): void
    {
        Membership::create(['user_id' => $user->id, 'plan' => $billing, 'status' => 'active', 'mode' => 'test', 'paid_until' => now()->addMonth()]);
    }

    private function teamWithDrivers(User $owner, int $drivers): RacingTeam
    {
        $team = RacingTeam::create(['name' => 'Night Owls', 'tag' => 'NOW', 'owner_id' => $owner->id]);
        $team->members()->attach(User::factory()->count($drivers - 1)->create()->pluck('id'));

        return $team;
    }

    public function test_six_seats_without_a_membership_unlimited_with_one(): void
    {
        $driver = User::factory()->create();
        $this->assertSame(6, $driver->teamSeatLimit());

        $this->supporter($driver, 'yearly');
        $this->assertNull($driver->fresh()->teamSeatLimit());
    }

    public function test_a_full_team_cannot_invite_and_pending_invites_hold_a_seat(): void
    {
        $owner = User::factory()->create();
        $team = $this->teamWithDrivers($owner, 5);

        $sixth = User::factory()->create();
        $this->actingAs($owner)->post(route('racing-teams.members.add', $team), ['user_id' => $sixth->id])->assertSessionHasNoErrors();

        // 5 drivers + 1 pending invite = 6 seats taken.
        $seventh = User::factory()->create();
        $this->actingAs($owner)->post(route('racing-teams.members.add', $team), ['user_id' => $seventh->id])->assertSessionHasErrors('team');
        $this->assertSame(1, RacingTeamInvitation::count());

        $this->actingAs($owner)->get(route('racing-teams.index'))->assertOk()->assertSee('5 / 6 drivers');
    }

    public function test_an_invite_cannot_be_accepted_once_the_team_is_full(): void
    {
        $owner = User::factory()->create();
        $this->supporter($owner);
        $team = $this->teamWithDrivers($owner, 7);
        $invited = User::factory()->create();
        $invitation = RacingTeamInvitation::create(['racing_team_id' => $team->id, 'user_id' => $invited->id]);

        // The owner's membership runs out: back to 6 seats, the team keeps its 7 drivers.
        Membership::where('user_id', $owner->id)->update(['paid_until' => now()->subMinute()]);

        $this->actingAs($invited)->post(route('racing-teams.invite.accept', $invitation))->assertSessionHasErrors('team');
        $this->assertSame(7, $team->fresh()->driverCount());
    }

    public function test_a_supporters_team_has_no_seat_limit(): void
    {
        $owner = User::factory()->create();
        $this->supporter($owner);
        $team = $this->teamWithDrivers($owner, 15);

        $this->actingAs($owner)->post(route('racing-teams.members.add', $team), ['user_id' => User::factory()->create()->id])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('racing-teams.index'))->assertSee('15 / ∞ drivers');
    }

    public function test_the_discord_supporter_role_follows_the_membership(): void
    {
        config([
            'services.discord.bot_token' => 'x', 'services.discord.guild_id' => 'G1', 'services.discord.rank_roles' => [],
            'services.discord.supporter_role_id' => '111',
        ]);
        $supporter = User::factory()->create();
        $lapsed = User::factory()->create();
        foreach ([[$supporter, 'D1'], [$lapsed, 'D2']] as [$user, $discordId]) {
            ConnectedAccount::create(['user_id' => $user->id, 'provider' => 'discord', 'provider_id' => $discordId, 'username' => 'd', 'connected_at' => now()]);
        }
        $this->supporter($supporter);

        Http::fake([
            '*/guilds/G1/members/D1' => Http::response(['roles' => []]),
            '*/guilds/G1/members/D2' => Http::response(['roles' => ['111']]),
            '*' => Http::response(null, 204),
        ]);

        app(DiscordRoleService::class)->syncUser($supporter->fresh());
        app(DiscordRoleService::class)->syncUser($lapsed->fresh());

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/members/D1/roles/111'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/members/D2/roles/111'));
    }
}
