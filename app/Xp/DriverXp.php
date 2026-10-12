<?php

namespace App\Xp;

use App\Models\Achievement;
use App\Models\AchievementAward;
use App\Models\RaceResult;
use App\Models\User;
use App\Models\UserMetric;
use App\Models\XpLedgerEntry;
use App\Models\XpRankUp;
use Illuminate\Support\Collection;

// What the profile shows of a driver's XP: the Driver Level and its progress bar, the
// achievement collection with progress, and the reveal of everything earned since the
// driver last looked. Reads only, apart from markSeen().
class DriverXp
{
    private const ICONS = [
        'Results' => 'fa-solid fa-trophy',
        'Participation' => 'fa-solid fa-flag-checkered',
        'Clean Driving' => 'fa-solid fa-shield-halved',
        'Consistency' => 'fa-solid fa-chart-line',
        'Variety' => 'fa-solid fa-shuffle',
        'Rating' => 'fa-solid fa-ranking-star',
        'Nordschleife' => 'fa-solid fa-mountain',
        'Championship' => 'fa-solid fa-crown',
        'Time Trial' => 'fa-solid fa-stopwatch',
        'Cross Title' => 'fa-solid fa-gamepad',
        'Per Title' => 'fa-solid fa-car-side',
        'Community' => 'fa-solid fa-people-group',
        'Stewarding' => 'fa-solid fa-scale-balanced',
        'Supporter' => 'fa-solid fa-heart',
        'Coaching' => 'fa-solid fa-graduation-cap',
    ];

    public function __construct(private RankCurve $curve, private MetricRegistry $metrics) {}

    public static function icon(?string $category): string
    {
        return self::ICONS[$category] ?? 'fa-solid fa-medal';
    }

    // Level, XP into it and XP it takes, for a total. At the maximum there is no next level.
    /** @return array{total: int, level: int, into: int, needed: ?int, next: ?int, percent: float, max: bool} */
    public function position(int $total): array
    {
        $level = $this->curve->rankFor($total);
        $start = (int) $this->curve->xpForRank($level);
        $next = $this->curve->xpForRank($level + 1);
        $needed = $next === null ? null : $next - $start;

        return [
            'total' => $total,
            'level' => $level,
            'into' => $total - $start,
            'needed' => $needed,
            'next' => $next === null ? null : $level + 1,
            'percent' => $needed ? round(min(1, ($total - $start) / $needed) * 100, 1) : 100.0,
            'max' => $next === null,
        ];
    }

    public function summary(User $user): array
    {
        return $this->position((int) $user->xp_total);
    }

    // The collection: what the driver can earn today, by category, each tiered family as
    // one item. Left out: achievements on a metric the site cannot count yet and those of
    // a title nobody has raced here, unless the driver holds one. An archived achievement
    // shows only for a driver who earned it.
    /** @return array{earned: int, total: int, categories: Collection} */
    public function collection(User $user): array
    {
        $held = AchievementAward::where('user_id', $user->id)->get()->keyBy('achievement_id');
        $values = UserMetric::where('user_id', $user->id)->get()
            ->mapWithKeys(fn (UserMetric $m) => [$m->metric_key.'|'.$m->scope => (float) $m->value]);
        $countable = array_keys($this->metrics->countable());
        $titles = $this->titlesWithResults();

        $shown = Achievement::orderBy('sort_order')->orderBy('threshold')->get()->filter(function (Achievement $a) use ($held, $countable, $titles) {
            if ($held->has($a->id)) {
                return true;
            }

            return ! $a->is_archived
                && in_array($a->metric_key, $countable, true)
                && ($a->game_scope === Achievement::SCOPE_ALL || in_array($a->game_scope, $titles, true));
        });

        $categories = $shown->groupBy('category')->map(fn (Collection $rows, $category) => [
            'name' => $category,
            'icon' => self::icon($category),
            'families' => $rows->groupBy(fn (Achievement $a) => ($a->family ?: $a->code).'|'.$a->game_scope)->values()
                ->map(fn (Collection $tiers) => $this->family($tiers->values(), $held, $values)),
        ])->values();

        return [
            'earned' => $shown->filter(fn (Achievement $a) => $held->has($a->id))->count(),
            'total' => $shown->count(),
            'categories' => $categories,
        ];
    }

    // One family: the tier the driver is working on (or the last one, when all are held).
    private function family(Collection $tiers, Collection $held, Collection $values): array
    {
        $target = $tiers->first(fn (Achievement $a) => ! $held->has($a->id));
        $shown = $target ?? $tiers->last();
        $value = $values->get($shown->metric_key.'|'.$shown->game_scope, 0.0);
        $percent = $shown->threshold > 0 && $shown->comparison !== 'lte'
            ? min(100, round($value / $shown->threshold * 100, 1))
            : 0.0;

        return [
            'name' => $shown->name,
            'description' => $shown->description,
            'scope' => $shown->game_scope,
            'xp' => $shown->xp,
            'complete' => $target === null,
            'value' => min($value, $shown->threshold),
            'threshold' => $shown->threshold,
            'percent' => $target === null ? 100.0 : $percent,
            'tiers' => $tiers->map(fn (Achievement $a) => $held->has($a->id))->all(),
            'earned' => $tiers->filter(fn (Achievement $a) => $held->has($a->id))->count(),
            'earned_xp' => (int) $tiers->sum(fn (Achievement $a) => $held->get($a->id)?->xp_awarded ?? 0),
        ];
    }

    // Everything earned since the driver last looked, shaped for the reveal (xp-reveal.js):
    // null when there is nothing new. One race shows its own lines; several show a line each.
    public function reveal(User $user): ?array
    {
        $entries = XpLedgerEntry::where('user_id', $user->id)->whereNull('seen_at')->orderBy('id')->get();
        if ($entries->isEmpty()) {
            return null;
        }

        $races = $entries->where('source', 'race')->values();
        $lines = [];

        if ($races->count() === 1) {
            $entry = $races->first();
            $breakdown = $entry->breakdown ?? [];
            foreach ($breakdown['lines'] ?? [] as $line) {
                $lines[] = ['label' => $line['label'], 'xp' => (int) $line['xp'], 'note' => $line['note'] ?? null];
            }
            $bonus = (int) ($breakdown['new_title_bonus'] ?? 0);
            $multiplied = $entry->xp - $bonus - (int) ($breakdown['subtotal'] ?? $entry->xp - $bonus);
            if ($multiplied !== 0) {
                $lines[] = ['label' => 'Bonus multipliers', 'xp' => $multiplied, 'note' => collect($breakdown['multipliers'] ?? [])
                    ->map(fn ($factor, $name) => ucfirst($name).' x'.rtrim(rtrim(number_format($factor, 2), '0'), '.'))->implode(', ')];
            }
            if ($bonus > 0) {
                $lines[] = ['label' => 'First race in this title', 'xp' => $bonus, 'note' => null];
            }
        } else {
            foreach ($races as $entry) {
                $lines[] = ['label' => $entry->reason ?: 'Race', 'xp' => $entry->xp, 'note' => null];
            }
        }

        foreach ($entries->whereNotIn('source', ['race', 'achievement']) as $entry) {
            $lines[] = ['label' => $entry->reason ?: 'XP granted', 'xp' => $entry->xp, 'note' => null];
        }

        $unlocked = $entries->where('source', 'achievement');
        $catalogue = Achievement::whereIn('id', $unlocked->pluck('reference_id'))->get()->keyBy('id');
        $achievements = $unlocked->map(function (XpLedgerEntry $entry) use ($catalogue) {
            $achievement = $catalogue->get($entry->reference_id);

            return [
                'name' => $achievement?->name ?? 'Achievement',
                'description' => $achievement?->description,
                'xp' => $entry->xp,
                'icon' => self::icon($achievement?->category),
            ];
        })->values()->all();

        $after = (int) XpLedgerEntry::where('user_id', $user->id)->sum('xp');
        $gained = (int) $entries->sum('xp');
        $before = $after - $gained;
        $from = $this->curve->rankFor($before);
        $to = min($this->curve->maxRank(), $this->curve->rankFor($after) + 1);

        return [
            'title' => $races->count() === 1 ? $races->first()->reason : ($races->count() > 1 ? $races->count().' races' : 'XP earned'),
            'eyebrow' => $races->isEmpty() ? 'New XP' : ($races->count() === 1 ? 'Race complete' : 'Since your last visit'),
            'lines' => $lines,
            'achievements' => $achievements,
            'before' => $before,
            'after' => $after,
            'gained' => $gained,
            // The part of the curve the reveal moves through: level => XP it starts at.
            'curve' => $this->curve->levels()->toBase()->only(range($from, $to))->map(fn ($level) => (int) $level->cumulative_xp)->all(),
        ];
    }

    // The driver has seen what there was to reveal.
    public function markSeen(User $user): void
    {
        // Through the query builder: the ledger model itself refuses mass edits it can't check.
        XpLedgerEntry::where('user_id', $user->id)->whereNull('seen_at')->toBase()->update(['seen_at' => now()]);
        AchievementAward::where('user_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now()]);
        XpRankUp::where('user_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now()]);
    }

    // XP titles somebody has a race result in, here.
    private function titlesWithResults(): array
    {
        return RaceResult::query()->whereNotNull('race_game')->distinct()->pluck('race_game')
            ->map(fn ($game) => Titles::game($game))->unique()->values()->all();
    }
}
