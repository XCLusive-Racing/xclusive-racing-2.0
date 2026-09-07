<?php

namespace App\Services\PracticeServer;

readonly class PracticeEntryListResult
{
    public function __construct(
        public array $config,
        public int $entryCount,
        public int $skippedCount,
    ) {
    }
}
