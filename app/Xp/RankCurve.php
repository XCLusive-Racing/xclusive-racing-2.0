<?php

namespace App\Xp;

use App\Models\Achievement;
use App\Models\RankLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// The rank curve (rank_levels). A driver's rank is always looked up here from their XP
// total, so the two can never disagree.
class RankCurve
{
    // The rank the title agnostic catalogue is fitted to.
    public const ANCHOR_RANK = 150;

    // The first steps are never refitted: they make the first session land on rank 5.
    public const FIXED_STEPS = [1 => 100, 2 => 150, 3 => 300, 4 => 500];

    private const STEP_ROUNDING = 25;

    // Per instance, never static: a queue worker lives on after an admin refits the curve.
    private ?Collection $levels = null;

    public function flush(): void
    {
        $this->levels = null;
    }

    /** @return Collection<int, RankLevel> keyed by rank */
    public function levels(): Collection
    {
        return $this->levels ??= RankLevel::orderBy('rank')->get()->keyBy('rank');
    }

    public function rankFor(int $xp): int
    {
        $rank = 1;
        foreach ($this->levels() as $level) {
            if ($xp < $level->cumulative_xp) {
                break;
            }
            $rank = $level->rank;
        }

        return $rank;
    }

    public function xpForRank(int $rank): ?int
    {
        return $this->levels()->get($rank)?->cumulative_xp;
    }

    public function maxRank(): int
    {
        return (int) ($this->levels()->keys()->last() ?? 1);
    }

    /** XP of the unarchived catalogue that counts in every title. */
    public function titleAgnosticXp(): int
    {
        return (int) Achievement::active()->where('game_scope', Achievement::SCOPE_ALL)->sum('xp');
    }

    public function titleSpecificXp(): int
    {
        return (int) Achievement::active()->where('game_scope', '!=', Achievement::SCOPE_ALL)->sum('xp');
    }

    /** @return array{catalogue: int, required: ?int, difference: ?int, title_specific: int} */
    public function health(): array
    {
        $catalogue = $this->titleAgnosticXp();
        $required = $this->xpForRank(self::ANCHOR_RANK);

        return [
            'catalogue' => $catalogue,
            'required' => $required,
            'difference' => $required === null ? null : $catalogue - $required,
            'title_specific' => $this->titleSpecificXp(),
        ];
    }

    // What refit() would do, without doing it: the steps (rank => XP to the next rank) that
    // make the title agnostic catalogue land on the anchor rank exactly. The first four
    // steps stay as they are, the steps up to the anchor are scaled together (rounded to
    // 25, the last one taking up the remainder), and every step from the anchor upwards is
    // left alone, so the climb from there to the maximum costs what it did before.
    /** @return array{possible: bool, reason: ?string, target: int, steps: array<int, int>, changed: int, before: array<int, int>, after: array<int, int>} */
    public function refitPreview(): array
    {
        $levels = $this->levels();
        $target = $this->titleAgnosticXp();
        $steps = $levels->filter(fn (RankLevel $l) => $l->xp_to_next !== null)->map(fn (RankLevel $l) => $l->xp_to_next)->all();
        $milestones = array_values(array_filter([50, 100, self::ANCHOR_RANK, 200, $this->maxRank()], fn ($r) => $levels->has($r)));
        $before = array_combine($milestones, array_map(fn ($r) => $levels[$r]->cumulative_xp, $milestones));

        $fail = fn (string $reason) => ['possible' => false, 'reason' => $reason, 'target' => $target, 'steps' => $steps, 'changed' => 0, 'before' => $before, 'after' => $before];

        if (! $levels->has(self::ANCHOR_RANK)) {
            return $fail('The curve has no rank '.self::ANCHOR_RANK.'.');
        }

        $fixed = array_sum(self::FIXED_STEPS);
        $scalable = range(count(self::FIXED_STEPS) + 1, self::ANCHOR_RANK - 1);
        $current = array_sum(array_map(fn ($r) => $steps[$r], $scalable));

        if ($target - $fixed < count($scalable) * self::STEP_ROUNDING) {
            return $fail('The title agnostic catalogue is too small to spread over '.self::ANCHOR_RANK.' ranks.');
        }

        $new = self::FIXED_STEPS + $steps;
        $factor = ($target - $fixed) / $current;
        foreach ($scalable as $rank) {
            $new[$rank] = max(self::STEP_ROUNDING, (int) round($steps[$rank] * $factor / self::STEP_ROUNDING) * self::STEP_ROUNDING);
        }
        // The last step before the anchor takes whatever rounding left over.
        $last = self::ANCHOR_RANK - 1;
        $new[$last] += $target - $fixed - array_sum(array_map(fn ($r) => $new[$r], $scalable));
        if ($new[$last] < 1) {
            return $fail('The steps cannot be rounded to fit this catalogue total.');
        }
        ksort($new);

        $cumulative = $this->cumulative($new);
        $after = array_combine($milestones, array_map(fn ($r) => $cumulative[$r], $milestones));

        return [
            'possible' => true, 'reason' => null, 'target' => $target, 'steps' => $new,
            'changed' => count(array_filter($new, fn ($xp, $rank) => $xp !== $steps[$rank], ARRAY_FILTER_USE_BOTH)),
            'before' => $before, 'after' => $after,
        ];
    }

    public function refit(): bool
    {
        $preview = $this->refitPreview();
        if (! $preview['possible']) {
            return false;
        }

        $cumulative = $this->cumulative($preview['steps']);

        DB::transaction(function () use ($preview, $cumulative) {
            foreach ($cumulative as $rank => $xp) {
                RankLevel::whereKey($rank)->update(['cumulative_xp' => $xp, 'xp_to_next' => $preview['steps'][$rank] ?? null]);
            }
        });

        $this->flush();

        return true;
    }

    /** rank => XP the rank starts at, from rank => XP to the next rank. */
    private function cumulative(array $steps): array
    {
        $total = 0;
        $cumulative = [1 => 0];
        foreach ($steps as $rank => $xp) {
            $total += $xp;
            $cumulative[$rank + 1] = $total;
        }

        return $cumulative;
    }
}
