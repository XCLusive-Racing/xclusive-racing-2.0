<?php

namespace App\Services\PracticeServer;

use Carbon\Carbon;

readonly class PracticeWindow
{
    public function __construct(
        public Carbon $windowStart,
        public Carbon $uploadAt,
        public Carbon $windowEnd,
    ) {
    }

    public function isAlreadyPast(): bool
    {
        return $this->uploadAt->isPast();
    }

    public function overlaps(Carbon $otherStart, Carbon $otherEnd): bool
    {
        return $this->windowStart->lt($otherEnd) && $otherStart->lt($this->windowEnd);
    }
}
