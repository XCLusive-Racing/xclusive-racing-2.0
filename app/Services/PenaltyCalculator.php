<?php

namespace App\Services;

class PenaltyCalculator
{
    /** Codes that carry no rating/SR consequence — processing these dismisses the report instead of resolving it. */
    public const NO_PENALTY_CODES = ['NONE', 'RI', 'INVALID', 'PENDING'];

    public static function codes(): array
    {
        return config('penalty_codes', []);
    }

    public static function code(string $code): ?array
    {
        return self::codes()[$code] ?? null;
    }

    public static function isNoPenalty(string $code): bool
    {
        return in_array($code, self::NO_PENALTY_CODES, true);
    }

    /** Session multiplier (S): Race = 1.0, Qualifying = 0.5, Practice = 0.2. */
    public static function sessionMultiplier(?string $sessionType): float
    {
        return match ($sessionType) {
            'R'     => 1.0,
            'Q'     => 0.5,
            'P'     => 0.2,
            default => 0.0,
        };
    }

    /** SR loss multiplier for the steward-selected severity multiplier (1x/2x/3x). */
    public static function srMultiplier(float|int $multiplier): float
    {
        return match ((int) round((float) $multiplier)) {
            2       => 1.1,
            3       => 1.2,
            default => 1.0,
        };
    }

    /**
     * R = reported driver's XCL rating, P = penalty value, M = steward multiplier, S = session multiplier.
     *
     * XCL Rating deduction  = R / 100 * P * M * S
     * Rating return to reporter = XCL Rating deduction / 2.7 if the incident happened in a Race session, else 0
     * SR deduction = base SR for the penalty code * SR multiplier for M
     */
    public static function calculate(string $penaltyCode, float|int $multiplier, ?string $sessionType, float $reportedRating): array
    {
        $penalty = self::code($penaltyCode);
        $p       = (float) ($penalty['value'] ?? 0);
        $baseSr  = (float) ($penalty['sr'] ?? 0);
        $s       = self::sessionMultiplier($sessionType);
        $m       = (float) $multiplier;

        $ratingDeduction = $reportedRating / 100 * $p * $m * $s;
        $ratingReturn    = $sessionType === 'R' ? ($ratingDeduction / 2.7) : 0.0;
        $srDeduction     = $baseSr * self::srMultiplier($m);

        return [
            'rating_deduction' => round($ratingDeduction, 4),
            'rating_return'    => round($ratingReturn, 4),
            'sr_deduction'     => round($srDeduction, 2),
        ];
    }
}
