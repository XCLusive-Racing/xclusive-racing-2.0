<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlatformLookupService
{
    public function __construct(private readonly PsnLookupService $psnLookup) {}

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout(10)->withOptions(['connect_timeout' => 5]);

        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    public function lookup(string $platform, string $identifier): array
    {
        return match ($platform) {
            'steam' => $this->lookupSteam($identifier),
            'xbox'  => $this->lookupXbox($identifier),
            'ps5'   => $this->lookupPsn($identifier),
            default => throw new RuntimeException("Unsupported platform: {$platform}"),
        };
    }

    // ── Steam ─────────────────────────────────────────────────────────────────

    private function lookupSteam(string $input): array
    {
        $apiKey = config('services.steam.api_key');

        try {
            if (!preg_match('/^\d{17}$/', $input)) {
                $res = $this->http()->get('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/', [
                    'key'       => $apiKey,
                    'vanityurl' => $input,
                ]);

                if ($res->json('response.success') !== 1) {
                    throw new RuntimeException('Steam account not found. Check your SteamID64 or vanity URL.');
                }

                $input = $res->json('response.steamid');
            }

            $res = $this->http()->get('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                'key'      => $apiKey,
                'steamids' => $input,
            ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach Steam. Please try again.');
        }

        $player = $res->json('response.players.0');
        if (!$player) {
            throw new RuntimeException('Steam account not found.');
        }

        return [
            'platform_id' => 'S' . $player['steamid'],
            'name'        => $player['personaname'],
        ];
    }

    // ── Xbox (OpenXBL) ────────────────────────────────────────────────────────

    private function lookupXbox(string $gamertag): array
    {
        // Strip the #xxxx discriminator — the Xbox API expects just the base name
        $baseTag = trim(preg_replace('/#\d+$/', '', trim($gamertag)));

        try {
            $res = $this->http()->withHeaders([
                'x-authorization' => config('services.openxbl.api_key'),
                'Accept'          => 'application/json',
                'Accept-Language' => 'en-US',
            ])->get('https://xbl.io/api/v2/friends/search', ['gt' => $baseTag]);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach Xbox Live. Please try again.');
        }

        if (!$res->successful()) {
            throw new RuntimeException('Xbox account not found. Check your Gamertag.');
        }

        $profile = $res->json('content.profileUsers.0');
        if (!$profile) {
            throw new RuntimeException('Xbox account not found. Check your Gamertag.');
        }

        $xuid = $profile['id'];
        $tag  = collect($profile['settings'] ?? [])->firstWhere('id', 'Gamertag')['value'] ?? $baseTag;

        return [
            'platform_id' => 'M' . $xuid,
            'name'        => $tag,
        ];
    }

    // ── PSN ───────────────────────────────────────────────────────────────────

    private function lookupPsn(string $onlineId): array
    {
        $data = $this->psnLookup->lookup($onlineId);

        return [
            'platform_id' => 'P' . $data['accountId'],
            'name'        => $data['onlineId'],
        ];
    }
}