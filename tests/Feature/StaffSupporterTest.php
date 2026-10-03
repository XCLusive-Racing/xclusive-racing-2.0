<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// XCL staff (admin panel roles) get the supporter badge and perks automatically while
// they hold the role; a paid supporter (is_supporter) keeps it after losing the role.
class StaffSupporterTest extends TestCase
{
    use RefreshDatabase;

    private function giveRole(User $user, string $slug): Role
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        $user->roles()->attach($role->id);

        return $role;
    }

    public function test_every_xcl_staff_role_makes_someone_a_supporter(): void
    {
        foreach (User::STAFF_SUPPORTER_ROLES as $slug) {
            $user = User::factory()->create(['is_supporter' => false]);
            $this->giveRole($user, $slug);

            $this->assertTrue($user->fresh()->isSupporter(), $slug);
        }
    }

    public function test_drivers_and_other_leagues_staff_are_not_supporters_by_role(): void
    {
        foreach (['driver', 'league_manager', 'league_steward'] as $slug) {
            $user = User::factory()->create(['is_supporter' => false]);
            $this->giveRole($user, $slug);

            $this->assertFalse($user->fresh()->isSupporter(), $slug);
        }
    }

    public function test_losing_the_staff_role_drops_the_badge_unless_they_pay(): void
    {
        $staff = User::factory()->create(['is_supporter' => false]);
        $paying = User::factory()->create(['is_supporter' => true]);
        $stewardForStaff = $this->giveRole($staff, 'steward');
        $this->giveRole($paying, 'steward');

        $staff->roles()->detach($stewardForStaff->id);
        $paying->roles()->detach($stewardForStaff->id);

        $this->assertFalse($staff->fresh()->isSupporter());
        $this->assertTrue($paying->fresh()->isSupporter());
    }

    public function test_staff_can_use_the_supporter_profile_perks(): void
    {
        $user = User::factory()->create(['is_supporter' => false, 'team' => null]);
        $this->giveRole($user, 'event_manager');

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => 'XCL Staff', 'stream_url' => 'https://twitch.tv/xclstaff'])
            ->assertSessionHasNoErrors();

        $this->assertSame('XCL Staff', $user->fresh()->team);
        $this->assertSame('XCL Staff', $user->fresh()->displayTeam());
    }
}
