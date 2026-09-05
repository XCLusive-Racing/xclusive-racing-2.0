<?php

namespace App\Console\Commands\Concerns;

trait NormalizesDiscordRoleId
{
    // Accepts either a raw snowflake ("123456789") or a pasted mention
    // ("<@&123456789>") in the env var — strips everything but digits so
    // either form works, instead of silently double-wrapping into <@&<@&...>>.
    private function normalizeRoleId(?string $roleId): ?string
    {
        if (!$roleId) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $roleId);

        return $digits ?: null;
    }
}
