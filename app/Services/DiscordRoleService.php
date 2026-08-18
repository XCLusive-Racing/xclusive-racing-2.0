<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordRoleService
{
    private const API_BASE = 'https://discord.com/api/v10';

    private function http(): PendingRequest
    {
        $client = Http::withToken(config('services.discord.bot_token'), 'Bot')
            ->timeout(10)
            ->withOptions(['connect_timeout' => 5])
            ->baseUrl(self::API_BASE);

        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Sync a user's Discord rank role to match their highest XCL rating rank
     * (see User::highestRank()). No-op if the user has no linked Discord
     * account, isn't in the configured guild (anymore), or the bot isn't
     * configured yet — never throws, since this must never block the caller
     * (rating calculation, account linking, admin edits).
     */
    public function syncUser(User $user): void
    {
        $discord = $user->connectedAccount('discord');
        if (! $discord) {
            return;
        }

        $guildId   = config('services.discord.guild_id');
        $rankRoles = array_filter(config('services.discord.rank_roles', []));

        if (! $guildId || ! $rankRoles) {
            return;
        }

        $targetRoleId = $rankRoles[$user->highestRank()['slug']] ?? null;
        if (! $targetRoleId) {
            return;
        }

        try {
            $member = $this->http()->get("/guilds/{$guildId}/members/{$discord->provider_id}");
        } catch (ConnectionException $e) {
            Log::warning('Discord rank sync: could not reach Discord', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return;
        }

        if ($member->status() === 404) {
            return; // no longer a member of the guild
        }

        if (! $member->successful()) {
            Log::warning('Discord rank sync: failed to fetch guild member', [
                'user_id' => $user->id,
                'status'  => $member->status(),
            ]);
            return;
        }

        $currentRoles = $member->json('roles', []);

        foreach ($rankRoles as $roleId) {
            $hasRole = in_array($roleId, $currentRoles, true);

            if ($roleId === $targetRoleId && ! $hasRole) {
                $this->modifyRole($guildId, $discord->provider_id, $roleId, add: true);
            } elseif ($roleId !== $targetRoleId && $hasRole) {
                $this->modifyRole($guildId, $discord->provider_id, $roleId, add: false);
            }
        }
    }

    private function modifyRole(string $guildId, string $memberId, string $roleId, bool $add): void
    {
        // Discord's per-route rate limit for role add/remove is easily hit during a bulk
        // sync of many members in quick succession — retry once on 429 using the
        // server-provided retry_after instead of just dropping the update.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $res = $add
                    ? $this->http()->put("/guilds/{$guildId}/members/{$memberId}/roles/{$roleId}")
                    : $this->http()->delete("/guilds/{$guildId}/members/{$memberId}/roles/{$roleId}");
            } catch (ConnectionException $e) {
                Log::warning('Discord rank sync: could not reach Discord', [
                    'member_id' => $memberId,
                    'role_id'   => $roleId,
                    'error'     => $e->getMessage(),
                ]);
                return;
            }

            if ($res->successful()) {
                return;
            }

            if ($res->status() === 429 && $attempt < 3) {
                usleep((int) ((float) ($res->json('retry_after') ?? 1) * 1_000_000) + 100_000);
                continue;
            }

            Log::warning('Discord rank sync: role update failed', [
                'member_id' => $memberId,
                'role_id'   => $roleId,
                'add'       => $add,
                'status'    => $res->status(),
            ]);
            return;
        }
    }
}
