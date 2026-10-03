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

// What each membership plan unlocks (config/memberships.php): My Team seats and the
// Discord plan roles. Every plan includes the plans below it.
class MembershipPerksTest extends TestCase
{
    use RefreshDatabase;

    private function plan(User $user, string $plan): void
    {
        Membership::create(['user_id' => $user->id, 'plan' => $plan, 'status' => 'active', 'mode' => 'test', 'paid_until' => now()->addMonth()]);
    }

    private function teamWithDrivers(User $owner, int $drivers): RacingTeam
    {
        $team = RacingTeam::create(['name' => 'Night Owls', 'tag' => 'NOW', 'owner_id' => $owner->id]);
        $team->members()->attach(User::factory()->count($drivers - 1)->create()->pluck('id'));

        return $team;
    }

    public function test_team_seats_per_plan(): void
    {
        $seats = [];
        foreach ([null, 'supporter', 'member', 'vip'] as $plan) {
            $owner = User::factory()->create();
            if ($plan) {
                $this->plan($owner, $plan);
            }
            $seats[$plan ?? 'none'] = $owner->fresh()->teamSeatLimit();
        }

        $this->assertSame(['none' => 6, 'supporter' => 8, 'member' => 12, 'vip' => null], $seats);
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

    public function test_a_supporter_plan_gives_two_extra_seats(): void
    {
        $owner = User::factory()->create();
        $this->plan($owner, 'supporter');
        $team = $this->teamWithDrivers($owner, 6);

        $this->actingAs($owner)->post(route('racing-teams.members.add', $team), ['user_id' => User::factory()->create()->id])->assertSessionHasNoErrors();
    }

    public function test_an_invite_cannot_be_accepted_once_the_team_is_full(): void
    {
        $owner = User::factory()->create();
        $this->plan($owner, 'supporter');
        $team = $this->teamWithDrivers($owner, 7);
        $invited = User::factory()->create();
        $invitation = RacingTeamInvitation::create(['racing_team_id' => $team->id, 'user_id' => $invited->id]);

        // The owner's plan runs out: back to 6 seats, the team keeps its 7 drivers.
        Membership::where('user_id', $owner->id)->update(['paid_until' => now()->subMinute()]);

        $this->actingAs($invited)->post(route('racing-teams.invite.accept', $invitation))->assertSessionHasErrors('team');
        $this->assertSame(7, $team->fresh()->driverCount());
    }

    public function test_a_vip_team_has_no_seat_limit(): void
    {
        $owner = User::factory()->create();
        $this->plan($owner, 'vip');
        $team = $this->teamWithDrivers($owner, 15);

        $this->actingAs($owner)->post(route('racing-teams.members.add', $team), ['user_id' => User::factory()->create()->id])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('racing-teams.index'))->assertSee('15 / ∞ drivers');
    }

    public function test_discord_plan_roles_are_cumulative_and_removed_when_the_plan_ends(): void
    {
        config([
            'services.discord.bot_token' => 'x', 'services.discord.guild_id' => 'G1', 'services.discord.rank_roles' => [],
            'services.discord.plan_roles' => ['supporter' => '111', 'member' => '222', 'vip' => '333'],
        ]);
        $user = User::factory()->create();
        ConnectedAccount::create(['user_id' => $user->id, 'provider' => 'discord', 'provider_id' => 'D1', 'username' => 'd', 'connected_at' => now()]);
        $this->plan($user, 'member');

        // Currently holds only the VIP role (e.g. a lapsed VIP who's now a Member).
        Http::fake([
            '*/guilds/G1/members/D1' => Http::response(['roles' => ['333']]),
            '*' => Http::response(null, 204),
        ]);

        app(DiscordRoleService::class)->syncUser($user->fresh());

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/roles/111'));
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/roles/222'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/roles/333'));
    }
}
