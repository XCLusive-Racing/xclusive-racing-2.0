<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 7 (docs/championships/PLAN.md): league branding + rules/prizes/requirements/
// penalties on the public championship page, and confirming the Discord entry-gate
// is actually explained at the point of registration.
class PublicChampionshipPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
            'discord_invite_url' => 'https://discord.gg/' . $slug,
        ]);
    }

    private function makeChampionship(League $league, array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    public function test_a_guest_sees_the_leagues_branding_on_the_championship_page(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        // No actingAs() — a genuine unauthenticated guest, the exact audience this
        // page is public for. Before the Phase 7 fix, League's own TenantScope
        // silently resolved $championship->league to null for anyone who wasn't
        // a member of that specific league, which is every ordinary visitor.
        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('NLRL');
    }

    public function test_a_logged_in_driver_with_no_league_membership_also_sees_the_branding(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $driver       = \App\Models\User::factory()->create();

        $this->actingAs($driver)
            ->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('NLRL');
    }

    public function test_rules_and_prizes_render_when_set(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'requirements' => ['notes' => 'No pit lane speeding.', 'prizes_text' => 'Winner gets a trophy.'],
        ]);
        $championship->save();

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('No pit lane speeding.')
            ->assertSee('Winner gets a trophy.');
    }

    public function test_rules_and_prizes_are_absent_when_not_set(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertDontSee('>Rules<', false)
            ->assertDontSee('>Prizes<', false);
    }

    public function test_entry_requirements_and_discord_notice_show_on_the_page(): void
    {
        $league = $this->makeLeague('nlrl');
        $league->update(['requires_discord_membership' => true]);
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'requirements' => ['min_xcl_rating_tier' => 'gold'],
        ]);
        $championship->save();

        $driver = \App\Models\User::factory()->create();

        $this->actingAs($driver)
            ->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Gold')
            ->assertSee('Discord membership required.', false);
    }

    public function test_championship_level_discord_opt_in_shows_the_notice_even_when_the_league_itself_does_not_require_it(): void
    {
        $league       = $this->makeLeague('nlrl'); // requires_discord_membership stays false
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'requirements' => ['discord_membership_required' => true],
        ]);
        $championship->save();

        $driver = \App\Models\User::factory()->create();

        $this->actingAs($driver)
            ->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Discord membership required.', false);
    }

    public function test_native_xcl_championship_does_not_show_the_settings_driven_sections(): void
    {
        $xcl          = League::system();
        $championship = $this->makeChampionship($xcl);

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertDontSee('Entry Requirements');
    }

    // Refinement request item 7: the single-class standings heading used to
    // hardcode the generic word "Championship" ("Championship Standings"),
    // redundant right below the hero already naming the actual championship —
    // now reads "Overall Standings" instead, same on a league page or XCL's own.
    public function test_single_class_standings_heading_says_overall_not_championship(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Overall Standings')
            ->assertDontSee('Championship Standings');
    }
}
