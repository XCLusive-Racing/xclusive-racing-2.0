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

    private function lookupXbox(string $input): array
    {
        $input = trim($input);

        // If the user enters a raw XUID (15–18 digit number), skip the gamertag
        // search entirely. This is the fallback for old/changed/private gamertags.
        // name = null signals to the controller to preserve the existing imported name.
        if (preg_match('/^\d{15,18}$/', $input)) {
            return ['platform_id' => 'M' . $input, 'name' => null];
        }

        // Strip discriminator suffix (#1234), normalize whitespace
        $baseTag = trim(preg_replace('/\s+/', ' ', preg_replace('/#\d+$/', '', $input)));

        $headers = [
            'x-authorization' => config('services.openxbl.api_key'),
            'Accept'          => 'application/json',
            'Accept-Language' => 'en-US',
        ];

        // Try with original tag, then without spaces (old-style gamertags)
        $candidates = array_unique([$baseTag, preg_replace('/\s+/', '', $baseTag)]);

        foreach ($candidates as $attempt) {
            try {
                $res = $this->http()->withHeaders($headers)
                    ->get('https://api.xbl.io/v2/search/' . rawurlencode($attempt));
            } catch (ConnectionException) {
                \Log::error('Xbox lookup connection failed', ['gt' => $attempt]);
                throw new RuntimeException('Could not reach Xbox Live. Please try again.');
            }

            if (! $res->successful()) {
                throw new RuntimeException('[DEBUG] status=' . $res->status() . ' body=' . $res->body());
            }

            $profile = $res->json('people.0') ?? $res->json('content.people.0');

            if ($profile) {
                return [
                    'platform_id' => 'M' . $profile['xuid'],
                    'name'        => $profile['modernGamertag'] ?? $profile['gamertag'] ?? $baseTag,
                ];
            }

            \Log::warning('OpenXBL empty result', ['gt' => $attempt, 'status' => $res->status(), 'body' => $res->json()]);
            throw new RuntimeException('[DEBUG] gt=' . $attempt . ' status=' . $res->status() . ' body=' . json_encode($res->json()));
        }

        throw new RuntimeException(
            'Xbox account not found for "' . $baseTag . '". ' .
            'Enter your current gamertag exactly as shown on your Xbox profile. ' .
            'If your gamertag recently changed or contains spaces, enter your XUID instead (the 15–16 digit number from your Xbox profile page).'
        );
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