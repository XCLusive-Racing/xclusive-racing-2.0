<?php

namespace Tests\Feature\Xp;

use App\Models\Achievement;
use App\Models\AchievementAward;
use App\Models\Game;
use App\Models\RankLevel;
use App\Models\Role;
use App\Models\User;
use App\Models\XpLedgerEntry;
use App\Xp\RankCurve;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Admin > XP & Achievements (docs/xp-achievements/PLAN.md, phase one).
class XpAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        return $admin;
    }

    private function fields(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Winner 750', 'description' => 'Win 750 races', 'category' => 'Results', 'game_scope' => 'all',
            'metric_key' => 'wins', 'threshold' => 750, 'xp' => 12000, 'sort_order' => 5,
        ];
    }

    public function test_only_owner_and_admin_can_open_the_screens(): void
    {
        $driver = User::factory()->create();

        foreach (['admin.xp.achievements.index', 'admin.xp.curve', 'admin.xp.games.index', 'admin.xp.adjustments.index', 'admin.xp.achievements.create'] as $route) {
            $this->actingAs($this->admin())->get(route($route))->assertOk();
            $this->actingAs($driver)->get(route($route))->assertStatus(403);
        }
    }

    public function test_the_list_filters_and_shows_how_many_drivers_hold_each_achievement(): void
    {
        $achievement = Achievement::where('code', 'wins_1')->firstOrFail();
        foreach (User::factory()->count(3)->create() as $user) {
            AchievementAward::create(['user_id' => $user->id, 'achievement_id' => $achievement->id, 'xp_awarded' => $achievement->xp, 'awarded_at' => now()]);
        }

        $listed = $this->actingAs($this->admin())
            ->get(route('admin.xp.achievements.index', ['family' => $achievement->family, 'scope' => 'all']))
            ->assertOk()->assertSee('keep it and keep the XP')
            ->viewData('achievements');

        $this->assertSame([$achievement->family], $listed->pluck('family')->unique()->values()->all());
        $this->assertSame(3, $listed->firstWhere('id', $achievement->id)->awards_count);

        // Archived ones are a filter away, never gone.
        $achievement->update(['is_archived' => true]);
        $this->assertFalse($this->get(route('admin.xp.achievements.index', ['family' => $achievement->family]))->viewData('achievements')->contains('id', $achievement->id));
        $this->assertTrue($this->get(route('admin.xp.achievements.index', ['family' => $achievement->family, 'archived' => 'yes']))->viewData('achievements')->contains('id', $achievement->id));
    }

    public function test_an_achievement_can_only_use_a_metric_from_the_registry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.xp.achievements.store'), $this->fields(['code' => 'wins_750', 'metric_key' => 'made_up_metric']))
            ->assertSessionHasErrors('metric_key');
        $this->assertFalse(Achievement::where('code', 'wins_750')->exists());

        $this->actingAs($admin)->post(route('admin.xp.achievements.store'), $this->fields(['code' => 'wins_750']))
            ->assertSessionHasNoErrors();
        $this->assertSame(12000, Achievement::where('code', 'wins_750')->value('xp'));

        $this->actingAs($admin)->get(route('admin.xp.achievements.create'))
            ->assertSee('must be added in code first')->assertSee('affects future awards only');
    }

    public function test_editing_and_archiving_leave_existing_awards_alone_and_nothing_can_be_deleted(): void
    {
        $admin = $this->admin();
        $achievement = Achievement::where('code', 'wins_1')->firstOrFail();
        $holder = User::factory()->create();
        AchievementAward::create(['user_id' => $holder->id, 'achievement_id' => $achievement->id, 'xp_awarded' => 75, 'awarded_at' => now()]);

        $this->actingAs($admin)->put(route('admin.xp.achievements.update', $achievement), $this->fields(['name' => 'First Win', 'threshold' => 1, 'xp' => 5000]))
            ->assertSessionHasNoErrors();
        $this->assertSame(5000, $achievement->fresh()->xp);
        $this->assertSame(75, AchievementAward::where('user_id', $holder->id)->value('xp_awarded'));

        $this->actingAs($admin)->post(route('admin.xp.achievements.archive', $achievement));
        $this->assertTrue($achievement->fresh()->is_archived);
        $this->assertSame(1, AchievementAward::where('achievement_id', $achievement->id)->count());

        $this->actingAs($admin)->delete('/admin/xp/achievements/'.$achievement->id)->assertStatus(405);
    }

    public function test_the_curve_health_panel_follows_catalogue_edits_and_the_refit_lines_it_up_again(): void
    {
        $admin = $this->admin();
        $curve = app(RankCurve::class);
        $this->assertSame(0, $curve->health()['difference']);

        // +10,000 XP on a title agnostic achievement: the catalogue is now above rank 150.
        Achievement::where('code', 'wins_1')->increment('xp', 10000);
        // A title specific edit moves nothing.
        Achievement::where('game_scope', 'lmu')->first()->increment('xp', 3000);

        $this->assertSame(10000, $curve->health()['difference']);
        $this->actingAs($admin)->get(route('admin.xp.achievements.index'))->assertOk()
            ->assertSee('10,000 XP above rank 150')->assertSee('does not affect rank 150, by design');

        $topBefore = $curve->xpForRank(250) - $curve->xpForRank(150);
        $this->actingAs($admin)->get(route('admin.xp.curve'))->assertOk()->assertSee('Apply the refit');

        $this->actingAs($admin)->post(route('admin.xp.curve.refit'))->assertRedirect(route('admin.xp.curve'));
        $curve = app(RankCurve::class); // a fresh look at the stored curve

        $this->assertSame(0, $curve->health()['difference']);
        $this->assertSame([100, 150, 300, 500], RankLevel::whereIn('rank', [1, 2, 3, 4])->orderBy('rank')->pluck('xp_to_next')->all());
        $this->assertSame($topBefore, $curve->xpForRank(250) - $curve->xpForRank(150));
        // The curve stays a curve: every rank starts above the one before it.
        $levels = RankLevel::orderBy('rank')->pluck('cumulative_xp')->all();
        $sorted = $levels;
        sort($sorted);
        $this->assertSame($sorted, $levels);
        $this->assertSame(count($levels), count(array_unique($levels)));
    }

    public function test_a_title_can_be_added_without_a_deploy(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.xp.games.store'), ['key' => 'rf2', 'name' => 'rFactor 2', 'sort_order' => 4])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.xp.games.store'), ['key' => 'all', 'name' => 'Everything'])->assertSessionHasErrors('key');

        $game = Game::where('key', 'rf2')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.xp.games.update', $game), ['name' => 'rFactor 2', 'sort_order' => 9])->assertSessionHasNoErrors();
        $this->assertFalse($game->fresh()->is_active);

        // It is a scope an achievement can now use.
        $this->actingAs($admin)->post(route('admin.xp.achievements.store'), $this->fields(['code' => 'rf2_wins_1', 'game_scope' => 'rf2']))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('admin.xp.games.index'))->assertOk()->assertSee('does not move the rank curve');
    }

    public function test_an_admin_can_grant_xp_with_a_reason_and_never_take_it_away(): void
    {
        $admin = $this->admin();
        $driver = User::factory()->create(['email' => 'driver@example.com']);

        $this->actingAs($admin)->post(route('admin.xp.adjustments.store'), ['driver' => 'driver@example.com', 'xp' => 2500])
            ->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.xp.adjustments.store'), ['driver' => 'driver@example.com', 'xp' => -500, 'reason' => 'Take it back'])
            ->assertSessionHasErrors('xp');
        $this->actingAs($admin)->post(route('admin.xp.adjustments.store'), ['driver' => 'nobody@example.com', 'xp' => 100, 'reason' => 'Unknown driver'])
            ->assertSessionHasErrors('driver');
        $this->assertSame(0, XpLedgerEntry::count());

        $this->actingAs($admin)->post(route('admin.xp.adjustments.store'), ['driver' => 'driver@example.com', 'xp' => 2500, 'reason' => 'Marshalled the 24 hour race'])
            ->assertSessionHasNoErrors();

        $entry = XpLedgerEntry::sole();
        $this->assertSame(['adjustment', 2500, 'Marshalled the 24 hour race', $admin->id], [$entry->source, $entry->xp, $entry->reason, $entry->reference_id]);
        $this->assertSame(2500, $driver->fresh()->xp_total);
        $this->assertSame(app(RankCurve::class)->rankFor(2500), $driver->fresh()->xp_rank);

        $this->actingAs($admin)->get(route('admin.xp.adjustments.index'))->assertOk()
            ->assertSee('Marshalled the 24 hour race')->assertSee('There is no way to take XP away');
    }
}
