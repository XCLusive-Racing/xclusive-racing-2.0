<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function attachManager(User $user, League $league): void
    {
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $user->id, 'role' => 'manager']);
        $user->syncLeagueRoleFlags();
        $user->refresh();
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());
        return $user;
    }

    private function makeChampionship(League $league): Championship
    {
        return Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    // --- Settings cast / upgrade path ---

    public function test_settings_cast_fills_defaults_for_an_empty_blob(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        $this->assertFalse($championship->settings->format->multiclass_enabled);
        $this->assertSame(30, $championship->settings->sessions->race_length_minutes);
        $this->assertSame('none', $championship->settings->penalties->affects);
    }

    public function test_settings_upgrade_fills_missing_keys_without_disturbing_stored_values(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        // Simulate a row saved under an older, smaller schema — only one key stored.
        $championship->setRawAttributes(array_merge($championship->getAttributes(), [
            'settings' => json_encode(['format' => ['multiclass_enabled' => true]]),
        ]));
        $championship->save();
        $championship->refresh();

        $this->assertTrue($championship->settings->format->multiclass_enabled);
        // Every other key the current schema defines is still there, defaulted.
        $this->assertSame(30, $championship->settings->sessions->race_length_minutes);
        $this->assertSame(0, $championship->settings->scoring->drop_rounds);
        $this->assertSame(ChampionshipSettingsSchema::CURRENT_VERSION, $championship->fresh()->settings_version);
    }

    public function test_a_step_save_only_touches_its_own_settings_group(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)->put(
            route('admin.leagues.championships.wizard.update', [$league, $championship, 'scoring']),
            ['settings' => ['scoring' => ['drop_rounds' => 3, 'fastest_lap_point' => '1', 'pole_point' => '0', 'team_points_enabled' => '0']]]
        )->assertRedirect();

        $championship->refresh();
        $this->assertSame(3, $championship->settings->scoring->drop_rounds);
        // Untouched groups still hold their defaults.
        $this->assertSame(30, $championship->settings->sessions->race_length_minutes);
    }

    // --- xcl_rating_enabled: policy + form request enforced, not just the view ---

    public function test_league_manager_cannot_enable_xcl_rating_through_any_step_save(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        // Attempt to smuggle it in via the penalties step payload — there is no
        // rule for it, and it isn't a settings key at all, so it's simply ignored.
        $this->actingAs($manager)->put(
            route('admin.leagues.championships.wizard.update', [$league, $championship, 'penalties']),
            [
                'xcl_rating_enabled' => '1',
                'settings' => ['penalties' => ['stewarding_enabled' => '0', 'affects' => 'none', 'post_race_time_penalties_enabled' => '0', 'xcl_rating_requested' => '1']],
            ]
        )->assertRedirect();

        $this->assertFalse($championship->fresh()->xcl_rating_enabled);
        $this->assertTrue($championship->fresh()->settings->penalties->xcl_rating_requested);
    }

    public function test_league_manager_cannot_reach_the_approve_rating_route(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.approve-rating', [$league, $championship]))
            ->assertForbidden();

        $this->assertFalse($championship->fresh()->xcl_rating_enabled);
    }

    public function test_admin_can_approve_rating_and_it_records_who_and_when(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        $this->actingAs($admin)
            ->post(route('admin.leagues.championships.approve-rating', [$league, $championship]))
            ->assertRedirect();

        $championship->refresh();
        $this->assertTrue($championship->xcl_rating_enabled);
        $this->assertNotNull($championship->xcl_rating_approved_at);
        $this->assertSame($admin->id, $championship->xcl_rating_approved_by);
    }

    // --- Tenant isolation on the new championships table ---

    public function test_league_manager_cannot_reach_another_leagues_championship(): void
    {
        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');

        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $nlrl);

        $srcChampionship = $this->makeChampionship($src);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$src, $srcChampionship, 'basics']))
            ->assertNotFound();
    }

    public function test_admin_bypassing_scope_still_cant_pair_a_championship_with_the_wrong_league_in_the_url(): void
    {
        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');
        $admin = $this->makeAdmin();

        $nlrlChampionship = $this->makeChampionship($nlrl);

        // Admin bypasses TenantScope entirely, so both models resolve — but the
        // URL pairing itself (championship belongs to a different league than
        // the one in the path) must still be rejected.
        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$src, $nlrlChampionship, 'basics']))
            ->assertNotFound();
    }
}
