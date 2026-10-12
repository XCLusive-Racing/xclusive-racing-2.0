<?php

namespace App\Xp\Metrics;

use App\Xp\DriverHistory;

// A metric the catalogue uses but the site cannot count yet. Registered so the admin can
// see it and why; its value is always null, so nothing on it is ever awarded.
class NotCountable extends Metric
{
    public function __construct(string $key, string $label, private string $why)
    {
        parent::__construct($key, $label);
    }

    public function countable(): bool
    {
        return false;
    }

    public function reason(): ?string
    {
        return $this->why;
    }

    protected function compute(DriverHistory $history, string $scope): int|float|bool|null
    {
        return null;
    }
}
