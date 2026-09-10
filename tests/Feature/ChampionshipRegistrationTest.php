<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\ConnectedAccount;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\DiscordRoleService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 4 (docs/championships/PLAN.md): entry-requirement thresholds sourced from
// settings for league championships, a spectator slot pool separate from
// max_drivers, and team registration for driver-swaps-enabled championships.
class ChampionshipRegistrationTest extends TestCase
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

    private function makeChampionship(League $league, array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    // --- Entry requirements sourced from settings ---

    public function test_league_championship_reads_requirement_thresholds_from_settings(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'requirements' => ['min_safety_rating' => 7.5, 'min_xcl_rating_tier' => 'gold'],
        ]);
        $championship->save();

        $thresholds = $championship->fresh()->requirementThresholds();

        $this->assertSame(7.5, $thresholds['sr']);
        $this->assertSame('gold', $thresholds['min']);
    }

    public function test_xcls_native_championship_still_reads_requirement_thresholds_from_flat_columns(): void
    {
        $xcl = League::system();
        $championship = $this->makeChampionship($xcl, ['sr_requirement' => '7', 'min_rating' => 'silver']);

        $thresholds = $championship->requirementThresholds();

        $this->assertSame('7', $thresholds['sr']);
        $this->assertSame('silver', $thresholds['min']);
    }

    public function test_registration_is_blocked_when_the_users_rating_is_below_the_settings_threshold(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'requirements' => ['min_xcl_rating_tier' => 'gold'],
        ]);
        $championship->save();

        $driver = User::factory()->create(['elo_acc' => 100]); // well below gold

        $this->actingAs($driver)
            ->post(route('championships.register', $championship))
            ->assertRedirect();

        $this->assertFalse($championship->fresh()->isRegistered($driver));
    }

    // --- Spectator slot pool ---

    public function test_driver_can_register_as_a_spectator_even_when_driver_slots_are_full(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['max_drivers' => 1]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['spectator_slots' => 5],
        ]);
        $championship->save();

        ChampionshipRegistration::create([
            'championship_id' => $championship->id,
            'user_id'         => User::factory()->create()->id,
        ]);
        $this->assertTrue($championship->fresh()->isFull());

        $spectator = User::factory()->create();
        $this->actingAs($spectator)
            ->post(route('championships.register', $championship), ['is_spectator' => 1])
            ->assertRedirect();

        $this->assertTrue($championship->fresh()->isRegistered($spectator));
        $this->assertDatabaseHas('championship_registrations', [
            'championship_id' => $championship->id, 'user_id' => $spectator->id, 'is_spectator' => true,
        ]);
        // A spectator must not count against the driver cap.
        $this->assertTrue($championship->fresh()->isFull());
    }

    public function test_spectator_registration_is_rejected_once_spectator_slots_are_full(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['spectator_slots' => 1],
        ]);
        $championship->save();

        ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => User::factory()->create()->id, 'is_spectator' => true,
        ]);

        $spectator = User::factory()->create();
        $this->actingAs($spectator)
            ->post(route('championships.register', $championship), ['is_spectator' => 1])
            ->assertRedirect();

        $this->assertFalse($championship->fresh()->isRegistered($spectator));
    }

    // --- Team registration (driver swaps) ---

    public function test_team_owner_can_register_their_team_for_a_driver_swaps_championship(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['driver_swaps_enabled' => true],
        ]);
        $championship->save();

        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $team   = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach($member->id);

        $this->actingAs($owner)
            ->post(route('championships.register', $championship), ['racing_team_id' => $team->id])
            ->assertRedirect();

        $this->assertDatabaseHas('championship_registrations', [
            'championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id,
        ]);
        // A team member (not the owner) reads as already registered too.
        $this->assertTrue($championship->fresh()->isRegistered($member));
    }

    public function test_a_user_cannot_register_someone_elses_team(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['driver_swaps_enabled' => true],
        ]);
        $championship->save();

        $owner   = User::factory()->create();
        $intruder = User::factory()->create();
        $team    = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);

        $this->actingAs($intruder)
            ->post(route('championships.register', $championship), ['racing_team_id' => $team->id])
            ->assertNotFound();
    }

    // --- Discord membership as an entry requirement ---

    public function test_registration_is_blocked_without_a_connected_discord_account(): void
    {
        $league = $this->makeLeague('nlrl');
        $league->update(['requires_discord_membership' => true, 'discord_guild_id' => '123']);
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->post(route('championships.register', $championship))
            ->assertRedirect();

        $this->assertFalse($championship->fresh()->isRegistered($driver));
    }

    public function test_registration_succeeds_when_the_discord_service_confirms_membership(): void
    {
        $league = $this->makeLeague('nlrl');
        $league->update(['requires_discord_membership' => true, 'discord_guild_id' => '123']);
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();
        ConnectedAccount::create(['user_id' => $driver->id, 'provider' => 'discord', 'provider_id' => '999', 'username' => 'driver#0001', 'connected_at' => now()]);

        $this->mock(DiscordRoleService::class, function ($mock) {
            $mock->shouldReceive('isGuildMember')->once()->with('123', '999')->andReturn(true);
        });

        $this->actingAs($driver)
            ->post(route('championships.register', $championship))
            ->assertRedirect();

        $this->assertTrue($championship->fresh()->isRegistered($driver));
    }

    public function test_registration_is_blocked_when_the_discord_service_says_not_a_member(): void
    {
        $league = $this->makeLeague('nlrl');
        $league->update(['requires_discord_membership' => true, 'discord_guild_id' => '123', 'discord_invite_url' => 'https://discord.gg/nlrl']);
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();
        ConnectedAccount::create(['user_id' => $driver->id, 'provider' => 'discord', 'provider_id' => '999', 'username' => 'driver#0001', 'connected_at' => now()]);

        $this->mock(DiscordRoleService::class, function ($mock) {
            $mock->shouldReceive('isGuildMember')->once()->andReturn(false);
        });

        $response = $this->actingAs($driver)->post(route('championships.register', $championship));
        $response->assertRedirect();
        $this->assertStringContainsString('discord.gg/nlrl', session('error'));

        $this->assertFalse($championship->fresh()->isRegistered($driver));
    }

    public function test_registration_is_blocked_when_discord_membership_cannot_be_verified(): void
    {
        $league = $this->makeLeague('nlrl');
        $league->update(['requires_discord_membership' => true, 'discord_guild_id' => '123']);
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();
        ConnectedAccount::create(['user_id' => $driver->id, 'provider' => 'discord', 'provider_id' => '999', 'username' => 'driver#0001', 'connected_at' => now()]);

        $this->mock(DiscordRoleService::class, function ($mock) {
            $mock->shouldReceive('isGuildMember')->once()->andReturn(null);
        });

        $this->actingAs($driver)
            ->post(route('championships.register', $championship))
            ->assertRedirect();

        $this->assertFalse($championship->fresh()->isRegistered($driver));
    }

    public function test_registration_is_allowed_when_the_league_requires_discord_but_never_configured_a_guild_id(): void
    {
        $league = $this->makeLeague('nlrl');
        $league->update(['requires_discord_membership' => true]); // no discord_guild_id
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->post(route('championships.register', $championship))
            ->assertRedirect();

        $this->assertTrue($championship->fresh()->isRegistered($driver));
    }

    public function test_public_show_page_renders_with_spectator_multiclass_and_driver_swaps_together(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['is_multiclass' => true]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['spectator_slots' => 3, 'driver_swaps_enabled' => true],
        ]);
        $championship->save();
        ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Pro', 'max_drivers' => 20]);

        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Register as Spectator')
            ->assertSee('Select Class')
            ->assertSee('driver swaps');
    }

    // --- Multiclass: real ChampionshipClass rows kept in sync from the wizard ---

    public function test_format_step_sync_creates_real_championship_class_rows_and_sets_is_multiclass(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['status' => 'draft', 'registration_open' => false]);
        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);

        $this->actingAs($manager)
            ->put(route('admin.leagues.championships.wizard.update', [$league, $championship, 'format']), [
                'settings' => ['format' => ['multiclass_enabled' => 1]],
                'classes_json' => json_encode([
                    ['name' => 'Pro', 'eligible_cars' => ['Ferrari 296 GT3'], 'max_entries' => 20],
                    ['name' => 'Am', 'eligible_cars' => ['Porsche 911 GT3 Cup'], 'max_entries' => 10],
                ]),
            ])
            ->assertRedirect();

        $championship->refresh();
        $this->assertTrue($championship->is_multiclass);
        $this->assertSame(['Pro', 'Am'], $championship->classes->pluck('name')->all());
        $this->assertSame(20, $championship->classes()->where('name', 'Pro')->value('max_drivers'));
    }

    public function test_removing_a_class_on_resave_deletes_it_but_keeping_a_class_preserves_its_registrations(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['status' => 'draft', 'registration_open' => false, 'is_multiclass' => true]);
        $pro = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Pro', 'max_drivers' => 20, 'sort_order' => 0]);
        $am  = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Am', 'max_drivers' => 10, 'sort_order' => 1]);

        ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => User::factory()->create()->id, 'championship_class_id' => $pro->id,
        ]);

        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);

        // Re-save the Format step keeping only "Pro" (renamed max) — "Am" drops off the list.
        $this->actingAs($manager)
            ->put(route('admin.leagues.championships.wizard.update', [$league, $championship, 'format']), [
                'settings' => ['format' => ['multiclass_enabled' => 1]],
                'classes_json' => json_encode([
                    ['name' => 'Pro', 'eligible_cars' => [], 'max_entries' => 25],
                ]),
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('championship_classes', ['id' => $am->id]);
        $this->assertDatabaseHas('championship_classes', ['id' => $pro->id, 'max_drivers' => 25]);
        // The registration tied to the class that was kept must survive.
        $this->assertDatabaseHas('championship_registrations', ['championship_class_id' => $pro->id]);
    }
}
