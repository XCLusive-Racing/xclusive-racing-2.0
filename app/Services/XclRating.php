<?php

namespace App\Services;

class XclRating
{
    public float $K_FACTOR = 50.0;
    public float $MULTIPLIER = 1.0;
    public float $STARTING_RATING = 1500.0;
    public float $STOP_LOSS_FLOOR = 500.0;
    public int   $MIN_DRIVERS = 8;

    public float $R_HIGH = 1.18;
    public float $R_LOW  = -0.85;

    public array $R_STATUS = [
        'DNF' => -0.50,
        'DNS' => -0.50,
        'DC'  => -0.20,
        'DSQ' => -0.70,
    ];

    public float $ELO_SCALE     = 800.0;
    public float $WIN_PCT_SCALE = 600.0;

    public float $EFF_RATING_ALPHA      = 0.55;
    public float $EFF_RATING_MIN_INPUT  = 500.0;
    public float $EFF_RATING_MAX_INPUT  = 10000.0;
    public float $EFF_RATING_MAX_OUTPUT = 2200.0;

    public float $PRESTIGE_GAMMA      = 1.5;
    public float $PRESTIGE_MIN_FACTOR = 0.05;
    public float $RATING_SOFT_MAX     = 12000.0;

    public array $LICENCE_THRESHOLDS = [
        'LEGEND'   => 10000,
        'ALIEN'    => 8000,
        'PLATINUM' => 6500,
        'GOLD'     => 5000,
        'SILVER'   => 3500,
        'BRONZE'   => 2000,
        'ROOKIE'   => 0,
    ];

    public array $DURATION_MULTIPLIERS = [
        '15'   => 0.6,
        '20'   => 0.8,
        '30'   => 1.0,
        '30+'  => 1.2,
        '30++' => 1.3,
        '45'   => 1.5,
        '45+'  => 1.6,
        '60'   => 2.0,
        '60+'  => 2.1,
        '90'   => 2.5,
        '90+'  => 2.6,
    ];

    /**
     * @throws \InvalidArgumentException
     */
    public function processRace(array $race, array $entries): array
    {
        $k          = (float) ($race['k_factor']  ?? $this->K_FACTOR);
        $multiplier = (float) ($race['multiplier'] ?? $this->MULTIPLIER);

        $finishers    = array_filter($entries, fn($e) => $e['status'] === 'FIN');
        $nonFinishers = array_filter($entries, fn($e) => $e['status'] !== 'FIN');

        $nFin   = count($finishers);
        $nTotal = count($entries);

        if ($nFin < $this->MIN_DRIVERS) {
            throw new \InvalidArgumentException(
                "Need at least {$this->MIN_DRIVERS} classified finishers; got {$nFin}."
            );
        }

        $sof   = array_sum(array_column($entries, 'rating')) / $nTotal;
        $rStep = ($this->R_HIGH - $this->R_LOW) / ($nFin - 1);

        $transformedRatings = [];
        $sumTransformed     = 0.0;

        foreach ($finishers as $e) {
            $tr = $this->transformedRating($e['rating']);
            $transformedRatings[$e['driver_id']] = $tr;
            $sumTransformed += $tr;
        }

        $results = [];

        foreach ($entries as $entry) {
            $driverId     = $entry['driver_id'];
            $oldRating    = (float) $entry['rating'];
            $status       = strtoupper($entry['status']);
            $finishPos    = $entry['finish_pos'] ?? null;
            $licenceBefore = $this->getLicence($oldRating);

            if ($status === 'FIN') {
                $rFactor     = $this->R_HIGH - ($finishPos - 1) * $rStep;
                $winPct      = $sumTransformed > 0
                    ? $transformedRatings[$driverId] / $sumTransformed
                    : 0.0;
                $actualScore = ($nTotal - $finishPos) / ($nTotal - 1);
                $expScore    = $this->expectedScore($oldRating, $sof);
                $rawChange   = $k * $multiplier * ($rFactor - $winPct)
                             + $k * $multiplier * ($actualScore - $expScore);
                $gainFactor  = $rawChange > 0 ? $this->gainFactor($oldRating, $sof) : 1.0;
                $eloChange   = $rawChange * $gainFactor;

                if ($oldRating <= $this->STOP_LOSS_FLOOR) {
                    $eloChange = max(0.0, $eloChange);
                }
            } else {
                $rFactor     = $this->R_STATUS[$status] ?? $this->R_STATUS['DNF'];
                $winPct      = 0.0;
                $actualScore = 0.0;
                $expScore    = 0.0;
                $rawChange   = $k * $multiplier * $rFactor;
                $gainFactor  = 1.0;
                $eloChange   = $rawChange;
            }

            $newRating    = $oldRating + $eloChange;
            $licenceAfter = $this->getLicence($newRating);

            $results[] = array_merge($entry, [
                'rating_before'  => $oldRating,
                'r_factor'       => round($rFactor,     6),
                'win_pct'        => round($winPct,      6),
                'actual_score'   => round($actualScore, 6),
                'exp_score'      => round($expScore,    6),
                'raw_change'     => round($rawChange,   4),
                'gain_factor'    => round($gainFactor,  6),
                'elo_change'     => round($eloChange,   4),
                'rating_after'   => round($newRating,   4),
                'licence_before' => $licenceBefore,
                'licence_after'  => $licenceAfter,
                'sof'            => round($sof,         2),
            ]);
        }

        usort($results, fn($a, $b) => ($a['finish_pos'] ?? PHP_INT_MAX) <=> ($b['finish_pos'] ?? PHP_INT_MAX));

        return $results;
    }

    public function setDuration(string $durationKey): static
    {
        if (! isset($this->DURATION_MULTIPLIERS[$durationKey])) {
            $valid = implode(', ', array_keys($this->DURATION_MULTIPLIERS));
            throw new \InvalidArgumentException(
                "Unknown duration key '{$durationKey}'. Valid keys: {$valid}"
            );
        }

        $this->MULTIPLIER = $this->DURATION_MULTIPLIERS[$durationKey];
        return $this;
    }

    public function getLicence(float $rating): string
    {
        foreach ($this->LICENCE_THRESHOLDS as $name => $threshold) {
            if ($rating >= $threshold) {
                return $name;
            }
        }
        return 'ROOKIE';
    }

    private function effectiveRating(float $rating): float
    {
        $rMin = $this->EFF_RATING_MIN_INPUT;
        $rMax = $this->EFF_RATING_MAX_INPUT;
        $tMin = $rMin;
        $tMax = $this->EFF_RATING_MAX_OUTPUT;

        if ($rating <= $rMin) return $tMin;
        if ($rating >= $rMax) return $tMax;

        $normalized = ($rating - $rMin) / ($rMax - $rMin);
        return $tMin + ($tMax - $tMin) * pow($normalized, $this->EFF_RATING_ALPHA);
    }

    private function transformedRating(float $rating): float
    {
        return 1.0 + pow(10.0, $this->effectiveRating($rating) / $this->WIN_PCT_SCALE);
    }

    private function expectedScore(float $rating, float $sof): float
    {
        return 1.0 / (1.0 + pow(10.0, ($sof - $rating) / $this->ELO_SCALE));
    }

    private function gainFactor(float $rating, float $sof): float
    {
        if ($rating <= $sof) {
            return 1.0;
        }

        $factor = pow($sof / $rating, $this->PRESTIGE_GAMMA);
        return max($this->PRESTIGE_MIN_FACTOR, min(1.0, $factor));
    }
}