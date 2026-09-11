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

    // Event-maker option parity: Basics used to only offer acc/lmu (the race form
    // offers acc/ac/lmu/iracing), had no Description field despite
    // Championship::description being a real fillable column, and used a plain file
    // input instead of <x-media-picker>'s gallery-pick flow.
    public function test_basics_step_accepts_every_game_saves_the_description_and_a_gallery_picked_image(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)->put(
            route('admin.leagues.championships.wizard.update', [$league, $championship, 'basics']),
            [
                'name' => 'Test Cup', 'slug' => 'test-cup-' . $championship->id,
                'game' => 'ac', 'platform' => 'pc', 'visibility' => 'public',
                'description' => 'A friendly ACC PC series.',
                'image_path'  => 'images/media/picked-from-gallery.jpg',
                'settings' => [
                    'schedule' => ['recurrence' => 'weekly', 'time_of_day' => '14:00'],
                ],
            ]
        )->assertRedirect();

        $championship->refresh();
        $this->assertSame('ac', $championship->game);
        $this->assertSame('A friendly ACC PC series.', $championship->description);
        $this->assertSame('images/media/picked-from-gallery.jpg', $championship->image);
    }

    // 2026-09-11: Sessions was split back out of Basics into its own step
    // (was growing past 15 fields, badly unbalancing the wizard's step sizes —
    // user feedback: "1 kopje heel veel ... andere bijna niks"). Confirms the
    // split step actually saves, and that Basics no longer touches it.
    public function test_sessions_is_its_own_step_independent_of_basics(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)->put(
            route('admin.leagues.championships.wizard.update', [$league, $championship, 'sessions']),
            [
                'settings' => [
                    'sessions' => [
                        'race_length_minutes' => 45, 'weather_mode' => 'fixed', 'formation_lap_type' => 'full',
                        'ingame_time_of_day' => '18:00', 'xcl_r_multiplier' => '2.0', 'pitstop_count' => '1',
                    ],
                ],
            ]
        )->assertRedirect()->assertSessionDoesntHaveErrors();

        $championship->refresh();
        $this->assertSame(45, $championship->settings->sessions->race_length_minutes);
        $this->assertEquals(2.0, $championship->settings->sessions->xcl_r_multiplier);
        // Untouched groups still hold their defaults.
        $this->assertSame('weekly', $championship->settings->schedule->recurrence);
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
                'settings' => ['penalties' => ['stewarding_enabled' => '0', 'affects' => 'none', 'post_race_time_penalties_enabled' => '0']],
            ]
        )->assertRedirect();

        $this->assertFalse($championship->fresh()->xcl_rating_enabled);
    }

    // User-directed 2026-09: approveRating's gate is now the same as update()
    // — a league manager can enable/disable XCL Rating for their own
    // championship directly, no XCL admin approval step needed (was
    // XCL-staff-only before this).
    public function test_league_manager_can_approve_and_revoke_rating_for_their_own_championship(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.approve-rating', [$league, $championship]))
            ->assertRedirect();

        $this->assertTrue($championship->fresh()->xcl_rating_enabled);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.revoke-rating', [$league, $championship]))
            ->assertRedirect();

        $this->assertFalse($championship->fresh()->xcl_rating_enabled);
    }

    // The tenant boundary still matters — managing a *different* league must
    // not be enough. League itself is Tenantable, so implicit route-model
    // binding for a league the outsider isn't a member of 404s before the
    // controller/policy is even reached — same as every other cross-league
    // test in this file (test_league_manager_cannot_reach_another_leagues_championship).
    public function test_a_different_leagues_manager_still_cannot_approve_rating(): void
    {
        $owner        = $this->makeLeague('nlrl');
        $otherLeague  = $this->makeLeague('src');
        $outsider     = User::factory()->leagueManager()->create();
        $this->attachManager($outsider, $otherLeague);
        $championship = $this->makeChampionship($owner);

        $this->actingAs($outsider)
            ->post(route('admin.leagues.championships.approve-rating', [$owner, $championship]))
            ->assertNotFound();

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

    // User-directed 2026-09: an enable/disable control for XCL Rating on
    // Basics (not just Review), and XCL-R Multiplier (Sessions step) actually
    // un-greys once it's on — same approve-rating/revoke-rating routes/policy
    // as before, just a second, more visible entry point.
    public function test_basics_step_shows_the_rating_toggle_for_an_admin_and_unlocks_the_multiplier(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'basics']))
            ->assertOk()
            ->assertSee('Disabled — click to enable');

        // Sessions step: the multiplier field is disabled while rating is off.
        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'sessions']))
            ->assertOk()
            ->assertSee('disabled', false);

        $this->actingAs($admin)
            ->post(route('admin.leagues.championships.approve-rating', [$league, $championship]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'basics']))
            ->assertOk()
            ->assertSee('Enabled — click to disable');

        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'sessions']))
            ->assertOk()
            ->assertDontSee('disabled', false);
    }

    public function test_basics_step_shows_the_rating_toggle_to_the_leagues_own_manager_too(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'basics']))
            ->assertOk()
            ->assertSee('Disabled — click to enable');
    }

    // User-directed 2026-09: the Classes builder ("+ Add Class") moved from
    // the bottom of the Format step to right under the Multiclass toggle
    // itself, and Car Class (the single, non-multiclass option) now hides
    // once Multiclass is on instead of sitting there looking equally
    // relevant. Both already share the same underlying toggle
    // (multiclass_enabled) — this only asserts the rendering reacts to it
    // correctly, not a second source of truth.
    private function isFieldHidden(string $html, string $fieldId): bool
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        // getElementById needs a DTD-declared ID attribute to work reliably against
        // loadHTML, which this markup doesn't have — an XPath id match doesn't.
        $xpath = new \DOMXPath($dom);
        $node = $xpath->query("//*[@id='{$fieldId}']")->item(0);
        return $node && $node->parentNode->attributes->getNamedItem('hidden') !== null;
    }

    public function test_format_step_hides_car_class_once_multiclass_is_on(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        // Multiclass off (default): Car Class is visible.
        $off = $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'format']))
            ->assertOk();
        $this->assertFalse($this->isFieldHidden($off->getContent(), 'f-format-car_class'));

        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['multiclass_enabled' => true],
        ]);
        $championship->save();

        // Multiclass on: Car Class is rendered hidden.
        $on = $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'format']))
            ->assertOk();
        $this->assertTrue($this->isFieldHidden($on->getContent(), 'f-format-car_class'));
    }

    // User-directed 2026-09 (corrected after an initial miss — "nee niet de
    // autos, je moet gwn de dropdown weer terug brengen met de classes"): a
    // championship class is picked from the same fixed GT2/GT3/GT4/TCX/GTC
    // dropdown the race wizard's own multiclass picker uses
    // (resources/js/components/multiclass.js), not a free-typed name with a
    // separately hand-picked car list.
    public function test_classes_builder_offers_the_fixed_class_dropdown(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        // The fixed 5-class list rows added via "+ Add Class" build from is
        // embedded once as JSON for the client-side script, independent of
        // whether the championship already has any saved classes.
        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'format']))
            ->assertOk()
            ->assertSee('Add Class')
            ->assertSee('["GT2","GT3","GT4","TCX","GTC"]', false)
            ->assertDontSee('Eligible cars')
            ->assertDontSee('comma separated');
    }

    // A class picked from the dropdown (name = "GT3") becomes that class's
    // car_class directly, same as the race wizard's own multiclass model —
    // no separate eligible_cars list to keep in sync any more.
    public function test_saving_classes_sets_car_class_from_the_picked_class_name(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)->put(
            route('admin.leagues.championships.wizard.update', [$league, $championship, 'format']),
            [
                'settings' => ['format' => ['multiclass_enabled' => 1]],
                'classes_json' => json_encode([
                    ['name' => 'GT3', 'max_entries' => 20],
                    ['name' => 'GT4', 'max_entries' => 10],
                ]),
            ]
        )->assertRedirect();

        $championship->refresh();
        $this->assertSame('GT3', $championship->classes()->where('name', 'GT3')->value('car_class'));
        $this->assertSame('GT4', $championship->classes()->where('name', 'GT4')->value('car_class'));
    }

    // User-directed 2026-09: "de button hierboven moet grayed zijn want dat
    // werkt og niet met de tekst vervanging door coming soon" — Discord
    // Membership Required is fully built (ChampionshipController::
    // discordMembershipFailure()) but not operationally ready (no XCL bot
    // actually installed in a real league's Discord yet, same reason the
    // League edit page's own Discord toggle is locked) — locked in the
    // wizard too via the schema's generic 'locked' flag.
    public function test_requirements_step_locks_the_discord_toggle(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'requirements']))
            ->assertOk()
            ->assertSee('disabled', false)
            ->assertSee('Temporarily locked');
    }

    // User-directed 2026-09: "bij ballast en restrictor mag hij met filters
    // van de carclass de autos pakken uit de db en bij driver uit de
    // entrylist en driver moet driver/team worden" — the Ballast &
    // Restrictor Adjustments builder's target field used to be free text;
    // it now offers real DB cars (filtered to the championship's own car
    // class(es)) for the "Car" scope, and the championship's actual entry
    // list for the (renamed) "Driver/Team" scope.
    public function test_adjustments_builder_offers_cars_filtered_by_the_championships_class(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);
        $championship->update(['car_class' => 'GT3']);

        \App\Models\Car::create(['id' => 1001, 'game' => 'acc', 'car_class' => 'GT3', 'name' => 'Audi R8 LMS GT3']);
        \App\Models\Car::create(['id' => 1002, 'game' => 'acc', 'car_class' => 'GT4', 'name' => 'BMW M4 GT4']);
        \App\Models\Car::create(['id' => 1003, 'game' => 'lmu', 'car_class' => 'GT3', 'name' => 'Some LMU GT3']);

        $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'penalties']))
            ->assertOk()
            ->assertSee('Audi R8 LMS GT3')
            ->assertDontSee('BMW M4 GT4')
            ->assertDontSee('Some LMU GT3')
            ->assertSee('Driver/Team');
    }

    public function test_adjustments_builder_offers_the_championships_own_entry_list(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        $solo = User::factory()->create(['name' => 'Solo Driver']);
        \App\Models\ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $solo->id, 'is_spectator' => false,
        ]);

        $teamOwner = User::factory()->create();
        $team = \App\Models\RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $teamOwner->id]);
        \App\Models\ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $teamOwner->id,
            'racing_team_id' => $team->id, 'is_spectator' => false,
        ]);

        $spectator = User::factory()->create(['name' => 'Just Watching']);
        \App\Models\ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $spectator->id, 'is_spectator' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'penalties']))
            ->assertOk();

        $response->assertSee('Solo Driver')
            ->assertSee('Apex Racing')
            ->assertDontSee('Just Watching');
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
