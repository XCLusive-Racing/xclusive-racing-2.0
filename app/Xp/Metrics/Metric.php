<?php

namespace App\Xp\Metrics;

use App\Models\User;
use App\Xp\DriverHistory;

// Something an achievement can count. A metric has a key (what achievements.metric_key
// points at), a label for the admin, and a value for a driver in a scope: 'all' counts
// every title together, a game key counts that title only.
//
// The scope is handled here and in the two subclasses, not by each metric: a race metric
// is handed the driver's rows already narrowed to the scope, and a fact that belongs to
// one title (a time trial, an ACC only circuit) is zero in any other title's scope.
//
// A metric whose data does not exist yet is registered as NotCountable: its value is null,
// never zero, and an achievement on it is never awarded.
abstract class Metric
{
    public function __construct(public readonly string $key, public readonly string $label) {}

    public function countable(): bool
    {
        return true;
    }

    /** Why it can't be counted yet (NotCountable only). */
    public function reason(): ?string
    {
        return null;
    }

    final public function value(User $user, string $scope = 'all'): ?float
    {
        if (! $this->countable()) {
            return null;
        }

        $value = $this->compute(DriverHistory::for($user), $scope);

        return $value === null ? null : (float) $value;
    }

    abstract protected function compute(DriverHistory $history, string $scope): int|float|bool|null;
}
