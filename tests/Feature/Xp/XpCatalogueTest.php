<?php

namespace Tests\Feature\Xp;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\RankLevel;
use App\Xp\MetricRegistry;
use App\Xp\RankCurve;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The seeded catalogue and rank curve (docs/xp-achievements/PLAN.md, phase one).
class XpCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_and_the_games_are_seeded(): void
    {
        $this->assertSame(['acc', 'lmu', 'iracing'], Game::orderBy('sort_order')->pluck('key')->all());
        $this->assertSame(399, Achievement::count());
        $this->assertSame(15, Achievement::distinct()->count('category'));
        $this->assertSame(1239360, (int) Achievement::sum('xp'));
        $this->assertSame(250, RankLevel::count());

        // The two Nordschleife families the sheet scoped "all" are ACC only.
        $this->assertSame(['acc'], Achievement::whereIn('metric_key', ['nords_best_lap', 'nords_valid_streak'])->distinct()->pluck('game_scope')->all());
        $this->assertSame('lte', Achievement::where('metric_key', 'nords_best_lap')->value('comparison'));
        $this->assertSame(450.0, (float) Achievement::where('metric_key', 'nords_best_lap')->min('threshold'));
    }

    public function test_the_title_agnostic_catalogue_adds_up_to_exactly_rank_150(): void
    {
        $curve = app(RankCurve::class);

        $this->assertSame($curve->xpForRank(150), (int) Achievement::where('game_scope', 'all')->sum('xp'));
        $this->assertSame(0, $curve->health()['difference']);

        // The first four steps are never refitted, and nothing above rank 150 moved.
        $this->assertSame([100, 150, 300, 500], RankLevel::whereIn('rank', [1, 2, 3, 4])->orderBy('rank')->pluck('xp_to_next')->all());
        $this->assertSame(1898125, $curve->xpForRank(250) - $curve->xpForRank(150));
        $this->assertNull(RankLevel::find(250)->xp_to_next);
    }

    public function test_every_metric_the_catalogue_uses_is_registered(): void
    {
        $registry = app(MetricRegistry::class);
        $used = Achievement::distinct()->pluck('metric_key');

        $this->assertSame([], $used->reject(fn ($key) => $registry->get($key) !== null)->values()->all());
    }

    public function test_rank_follows_the_curve(): void
    {
        $curve = app(RankCurve::class);

        $this->assertSame(1, $curve->rankFor(0));
        $this->assertSame(1, $curve->rankFor(99));
        $this->assertSame(2, $curve->rankFor(100));
        $this->assertSame(5, $curve->rankFor(1050));
        $this->assertSame(149, $curve->rankFor($curve->xpForRank(150) - 1));
        $this->assertSame(150, $curve->rankFor($curve->xpForRank(150)));
        $this->assertSame(250, $curve->rankFor(99_000_000));
    }
}
