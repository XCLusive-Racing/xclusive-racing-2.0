<?php

namespace Tests\Feature\Xp;

use App\Models\Achievement;
use App\Models\AchievementAward;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Models\XpLedgerEntry;
use App\Xp\DriverXp;
use App\Xp\RankCurve;
use App\Xp\XpAwarder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// XP on the profile (docs/xp-achievements/PLAN.md, phase two): the Driver Level bar, the
// achievement collection and the reveal of XP the driver has not seen yet.
class XpProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xp.enabled' => true, 'xp.launch_at' => now()->subYear()->toDateTimeString()]);
    }

    private function driver(): User
    {
        return User::factory()->create(['elo_acc' => 1500, 'elo_lmu' => 1500, 'elo_iracing' => 1500, 'sr_acc' => 4, 'sr_lmu' => 4, 'sr_iracing' => 4]);
    }

    // A finished ACC race won by $user against two others.
    private function race(User $user): Race
    {
        $race = Race::create(['title' => 'Sprint Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subDays(Race::count() + 1)]);

        foreach ([1, 2, 3] as $place) {
            $driver = $place === 1 ? $user : User::factory()->create();
            RaceResult::create([
                'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => $race->track, 'race_game' => 'acc',
                'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => 1,
                'user_id' => $driver->id, 'player_id' => 'P'.$driver->id, 'driver_name' => $driver->name,
                'car_number' => $place, 'position' => $place, 'lap_count' => 10, 'total_time' => 1_200_000 + $place * 5000,
                'dnf' => false, 'dns' => false,
            ]);
        }

        return $race;
    }

    public function test_the_level_bar_shows_the_way_to_the_next_level(): void
    {
        $user = $this->driver();
        $curve = app(RankCurve::class);
        app(XpAwarder::class)->adjust($user, $curve->xpForRank(3) + 40, 'Test');

        $xp = app(DriverXp::class)->summary($user->fresh());

        $this->assertSame(3, $xp['level']);
        $this->assertSame(40, $xp['into']);
        $this->assertSame($curve->xpForRank(4) - $curve->xpForRank(3), $xp['needed']);
        $this->assertSame(4, $xp['next']);

        $this->actingAs($user->fresh())->get(route('profile'))->assertOk()
            ->assertSee('Driver Level')->assertSee('XP to level 4');
    }

    public function test_a_new_driver_starts_at_level_one_with_an_empty_collection_to_earn(): void
    {
        $user = $this->driver();

        $response = $this->actingAs($user)->get(route('profile'))->assertOk()
            ->assertSee('Driver Level')->assertSee('Achievements')->assertDontSee('data-xp-reveal', false);

        $this->assertSame(1, $response->viewData('xp')['level']);
        $this->assertSame(0, $response->viewData('xpCollection')['earned']);
        $this->assertNull($response->viewData('xpReveal'));
    }

    public function test_the_collection_groups_tiers_and_hides_what_cannot_be_earned_yet(): void
    {
        $user = $this->driver();
        app(XpAwarder::class)->processRace($user, $this->race($user));

        $collection = app(DriverXp::class)->collection($user->fresh());
        $families = $collection['categories']->flatMap(fn ($category) => $category['families']);
        $shown = $collection['categories']->pluck('name');

        // Nothing on a metric the site cannot count, and no title nobody has raced here.
        $this->assertFalse($shown->contains('Coaching'));
        $this->assertFalse($families->contains('scope', 'lmu'));
        $this->assertTrue($families->contains('scope', 'acc'));
        $this->assertGreaterThan(0, $collection['earned']);
        $this->assertSame(AchievementAward::where('user_id', $user->id)->count(), $collection['earned']);

        // The wins family: tier one is held, the next tier is the one shown, with progress.
        $tiers = Achievement::where('metric_key', 'wins')->where('game_scope', 'all')->orderBy('threshold')->get();
        $wins = $families->first(fn ($family) => $family['scope'] === 'all' && $family['name'] === $tiers[1]->name);
        $this->assertNotNull($wins);
        $this->assertTrue($wins['tiers'][0]);
        $this->assertFalse($wins['tiers'][1]);
        $this->assertSame(1.0, (float) $wins['value']);
        $this->assertSame($tiers->count(), count($wins['tiers']));

        // A held achievement stays in the collection after it is archived.
        $tiers[0]->update(['is_archived' => true]);
        $this->assertSame($collection['earned'], app(DriverXp::class)->collection($user->fresh())['earned']);
    }

    public function test_the_reveal_shows_unseen_xp_once(): void
    {
        $user = $this->driver();
        app(XpAwarder::class)->processRace($user, $this->race($user));
        $total = (int) $user->fresh()->xp_total;

        $response = $this->actingAs($user->fresh())->get(route('profile'))->assertOk()->assertSee('data-xp-reveal', false);
        $reveal = $response->viewData('xpReveal');

        // Everything shown adds up to what was gained, and ends on the driver's total.
        $this->assertSame(0, $reveal['before']);
        $this->assertSame($total, $reveal['after']);
        $this->assertSame($total, array_sum(array_column($reveal['lines'], 'xp')) + array_sum(array_column($reveal['achievements'], 'xp')));
        $this->assertContains('First race in this title', array_column($reveal['lines'], 'label'));
        $this->assertNotEmpty($reveal['achievements']);
        $this->assertArrayHasKey(app(RankCurve::class)->rankFor($total) + 1, $reveal['curve']);

        // Seen: the next visit opens without it, and the ledger keeps every XP.
        $this->actingAs($user->fresh())->post(route('profile.xp.seen'))->assertNoContent();
        $this->assertSame(0, XpLedgerEntry::where('user_id', $user->id)->whereNull('seen_at')->count());
        $this->assertSame(0, AchievementAward::where('user_id', $user->id)->whereNull('seen_at')->count());
        $this->assertSame($total, (int) XpLedgerEntry::where('user_id', $user->id)->sum('xp'));
        $this->actingAs($user->fresh())->get(route('profile'))->assertOk()->assertDontSee('data-xp-reveal', false)->assertSee('Replay progress')->assertSee('View achievements');

        // It can be played again, and that marks nothing.
        $again = $this->actingAs($user->fresh())->get(route('profile', ['xp_replay' => 1]))->assertOk()
            ->assertSee('data-xp-reveal', false)->assertDontSee('data-xp-seen-url', false)->viewData('xpReveal');
        $this->assertSame([$reveal['before'], $reveal['after'], $reveal['lines']], [$again['before'], $again['after'], $again['lines']]);
    }

    public function test_several_races_are_revealed_as_a_line_each(): void
    {
        $user = $this->driver();
        app(XpAwarder::class)->processRace($user, $this->race($user));
        app(XpAwarder::class)->processRace($user, $this->race($user));

        $reveal = app(DriverXp::class)->reveal($user->fresh());

        $this->assertSame('2 races', $reveal['title']);
        $this->assertCount(2, $reveal['lines']);
        $this->assertSame($reveal['gained'], array_sum(array_column($reveal['lines'], 'xp')) + array_sum(array_column($reveal['achievements'], 'xp')));
    }
}
