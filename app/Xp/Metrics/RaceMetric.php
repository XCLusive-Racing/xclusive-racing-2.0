<?php

namespace App\Xp\Metrics;

use App\Xp\DriverHistory;
use Closure;

// A metric counted from the driver's race results. The closure gets the rows of the scope
// (DriverHistory::rows()), oldest first, so it never has to think about titles.
class RaceMetric extends Metric
{
    public function __construct(string $key, string $label, private Closure $count)
    {
        parent::__construct($key, $label);
    }

    protected function compute(DriverHistory $history, string $scope): int|float|bool|null
    {
        return ($this->count)($history->rows($scope), $history->user, $scope);
    }
}
