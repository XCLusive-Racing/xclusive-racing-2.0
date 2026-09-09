<?php

namespace App\Services;

use InvalidArgumentException;

// Pure math for the "linear" and "curved" points-table generators, plus the
// shared validation and per-round scoring-depth resolution every scheme type
// needs. No model or database access here on purpose — PointsScheme (the
// model) calls into this to resolve and store a table; keeping the math itself
// free of persistence concerns is also what lets the editor mirror it in a
// small hand-written JS copy for the live preview (see
// resources/views/admin/leagues/points-schemes/_form.blade.php) without a
// server round-trip on every keystroke.
class PointsSchemeGenerator
{
    // Named presets rather than a raw exponent — an organiser thinks in terms
    // of "how much winning is worth", not curve parameters. The exponent
    // shapes points(pos) = floor + (top-floor) * ((depth-pos)/(depth-1))^exponent:
    // higher exponent = steeper early drop, flatter tail.
    public const STEEPNESS_PRESETS = [
        'gentle'   => ['label' => 'Gentle', 'exponent' => 1.15],
        'standard' => ['label' => 'Standard', 'exponent' => 1.6],
        'steep'    => ['label' => 'Steep', 'exponent' => 2.4],
    ];

    // Resolves a config-time depth (how many positions to actually generate
    // into the stored table) from either a fixed count or a percentage against
    // a reference field size. This is only ever used to build the stored
    // table itself — the live, per-round scoring cutoff for a percentage-depth
    // scheme is resolved separately, per round, by scoringCutoff() below,
    // against that round's own classified-finisher count.
    public static function resolveDepth(array $config, int $referenceFieldSize): int
    {
        $type  = $config['depth_type'] ?? 'fixed';
        $value = (float) ($config['depth_value'] ?? 1);

        $depth = $type === 'percentage'
            ? (int) ceil($value / 100 * max($referenceFieldSize, 1))
            : (int) $value;

        return max(1, $depth);
    }

    // top=50, gap=2, depth=10, no floor -> 50,48,46,...,32 (10 positions).
    // Floor defaults to 0 (points never go negative) and, when a position's
    // raw value would drop to or below it, that position scores exactly the
    // floor and generation stops there rather than continuing further or
    // going negative.
    public static function linear(int $top, int $gap, int $depth, ?int $floor = null): array
    {
        if ($depth < 1) {
            throw new InvalidArgumentException('Scoring depth must be at least 1.');
        }

        $floor = $floor ?? 0;
        $table = [];

        for ($i = 0; $i < $depth; $i++) {
            $position = $i + 1;
            $points   = $top - $gap * $i;

            if ($points <= $floor) {
                $table[$position] = $floor;
                break;
            }

            $table[$position] = $points;
        }

        self::validateTable($table);

        return $table;
    }

    // Falls steeply across the first few positions and flattens toward the
    // floor. pos=1 always resolves to exactly $top, pos=$depth always resolves
    // to exactly $floor.
    public static function curved(int $top, int $floor, int $depth, string $steepness): array
    {
        if ($depth < 1) {
            throw new InvalidArgumentException('Scoring depth must be at least 1.');
        }

        $exponent = self::STEEPNESS_PRESETS[$steepness]['exponent'] ?? self::STEEPNESS_PRESETS['standard']['exponent'];

        $table    = [];
        $previous = null;

        for ($position = 1; $position <= $depth; $position++) {
            if ($depth === 1) {
                $points = $top;
            } else {
                $ratio  = ($depth - $position) / ($depth - 1);
                $points = (int) round($floor + ($top - $floor) * ($ratio ** $exponent));
            }

            // Rounding can occasionally produce a tie or a one-point increase
            // between adjacent positions on a shallow curve — clamp rather
            // than let a non-monotonic table slip through.
            if ($previous !== null && $points > $previous) {
                $points = $previous;
            }

            $table[$position] = $points;
            $previous         = $points;
        }

        self::validateTable($table);

        return $table;
    }

    public static function validateTable(array $table): void
    {
        if (empty($table)) {
            throw new InvalidArgumentException('A points table needs at least one scored position.');
        }

        $previous = null;
        foreach ($table as $position => $points) {
            if (!is_numeric($points)) {
                throw new InvalidArgumentException("Position {$position} has a non-numeric points value.");
            }
            if ($previous !== null && $points > $previous) {
                throw new InvalidArgumentException('A points table must be strictly non-increasing by finishing position.');
            }
            $previous = $points;
        }
    }

    // How many positions actually score in one specific round. Manual schemes
    // and fixed-depth generated schemes always score every position their
    // stored table defines. A percentage-depth scheme resolves its cutoff
    // fresh per round against that round's own classified-finisher count
    // (never the starting grid, so retirements elsewhere in the field don't
    // change what a classified finisher scores) — this is what makes
    // "percentage of the field" concrete without ever recomputing the stored
    // table itself.
    public static function scoringCutoff(string $type, array $config, int $tableLength, int $classifiedFinishers): int
    {
        if ($type === 'manual' || ($config['depth_type'] ?? 'fixed') === 'fixed') {
            return $tableLength;
        }

        $percent = (float) ($config['depth_value'] ?? 100);
        $cutoff  = (int) ceil($percent / 100 * max($classifiedFinishers, 0));

        return max(0, min($cutoff, $tableLength));
    }
}
