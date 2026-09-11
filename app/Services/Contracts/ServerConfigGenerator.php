<?php

namespace App\Services\Contracts;

use App\Models\FtpServer;
use App\Models\Race;

// Every method the push pipeline (PushGPortalConfigs, PushPracticeServerConfigJob,
// Admin\RaceController::pushConfig, RaceController::register()) actually calls on
// AccServerConfigService — extracted so a future LmuServerConfigService can be
// added without touching any of those call sites. bop()/carGroup() stay off this
// interface: they're an ACC-specific balance-of-performance concept with no
// established LMU equivalent yet, not part of the generic per-race config shape.
interface ServerConfigGenerator
{
    public function entryList(Race $race): array;

    public function configuration(Race $race, ?FtpServer $server = null): array;

    public function settings(Race $race, ?FtpServer $server = null): array;

    public function eventRules(?Race $race = null, ?FtpServer $server = null): array;

    public function assistRules(?FtpServer $server = null): array;

    public function defaultEventConfig(): array;

    public function defaultSettings(): array;

    public function defaultEventRules(): array;

    public function defaultAssistRules(): array;
}
