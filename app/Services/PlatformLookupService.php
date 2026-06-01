<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlatformLookupService
{
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
            'platform_id' => $player['steamid'],
            'name'        => $player['personaname'],
        ];
    }

    // ── Xbox (OpenXBL) ────────────────────────────────────────────────────────

    private function lookupXbox(string $gamertag): array
    {
        try {
            $res = $this->http()->withHeaders([
                'x-authorization' => config('services.openxbl.api_key'),
                'Accept'          => 'application/json',
                'Accept-Language' => 'en-US',
            ])->get('https://xbl.io/api/v2/friends/search', ['gt' => $gamertag]);
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
        $tag  = collect($profile['settings'] ?? [])->firstWhere('id', 'Gamertag')['value'] ?? $gamertag;

        return [
            'platform_id' => 'M' . $xuid,
            'name'        => $tag,
        ];
    }

    // ── PSN ───────────────────────────────────────────────────────────────────

    private function lookupPsn(string $onlineId): array
    {
        try {
            $res = $this->http()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get("https://psnprofiles.com/{$onlineId}");
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach PSN. Please try again.');
        }

        if (!$res->successful()) {
            throw new RuntimeException('PSN account not found. Check your Online ID.');
        }

        return [
            'platform_id' => 'P' . strtolower($onlineId),
            'name'        => $onlineId,
        ];
    }
}