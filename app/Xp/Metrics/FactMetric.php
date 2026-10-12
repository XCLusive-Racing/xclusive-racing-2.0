<?php

namespace App\Xp\Metrics;

use App\Xp\DriverHistory;
use Closure;

// A metric counted from something other than race results: the profile, teams, reports,
// time trials, the membership. $game says which title the fact belongs to, if any: a time
// trial is ACC, so in the lmu scope it counts as zero. A fact with no title (a linked
// Discord account) is the same in every scope.
class FactMetric extends Metric
{
    public function __construct(string $key, string $label, private Closure $count, private ?string $game = null)
    {
        parent::__construct($key, $label);
    }

    protected function compute(DriverHistory $history, string $scope): int|float|bool|null
    {
        if ($this->game !== null && $scope !== 'all' && $scope !== $this->game) {
            return 0;
        }

        return ($this->count)($history->user, $history, $scope);
    }
}
