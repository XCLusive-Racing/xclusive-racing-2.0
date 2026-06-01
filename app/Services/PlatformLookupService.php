<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlatformLookupService
{
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = $this->http();

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

        $profile = $res->json('profileUsers.0');
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
        $accessToken = $this->getPsnAccessToken();

        try {
            $res = $this->http()->withToken($accessToken)
                ->get("https://us-prof.np.community.playstation.net/userProfile/v1/users/{$onlineId}/profile2", [
                    'fields' => 'accountId,onlineId,currentOnlineId',
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach PSN. Please try again.');
        }

        if (!$res->successful()) {
            throw new RuntimeException('PSN account not found. Check your Online ID.');
        }

        $profile = $res->json('profile');

        return [
            'platform_id' => 'P' . $profile['accountId'],
            'name'        => $profile['currentOnlineId'] ?? $profile['onlineId'],
        ];
    }

    private function getPsnAccessToken(): string
    {
        if ($cached = Cache::get('psn_access_token')) {
            return $cached;
        }

        $refreshToken = Cache::get('psn_refresh_token') ?? config('services.psn.refresh_token');

        if ($refreshToken) {
            try {
                return $this->psnRefresh($refreshToken);
            } catch (RuntimeException) {
                // fall through to NPSSO
            }
        }

        $npsso = config('services.psn.npsso');
        if (!$npsso) {
            throw new RuntimeException('PSN is not configured. Contact the administrator.');
        }

        return $this->psnFromNpsso($npsso);
    }

    private function psnFromNpsso(string $npsso): string
    {
        $authRes = $this->http()->withOptions(['allow_redirects' => false])
            ->withHeaders(['Cookie' => "npsso={$npsso}"])
            ->get('https://ca.account.sony.com/api/authz/v3/oauth/authorize', [
                'access_type'   => 'offline',
                'client_id'     => '09515159-7237-4370-b4f0-315ef5c1ea3f',
                'redirect_uri'  => 'com.scee.psxandroid.scecompcall://redirect',
                'response_type' => 'code',
                'scope'         => 'psn:mobile.v2.core psn:clientapp',
            ]);

        $location = $authRes->header('Location');
        if (!$location) {
            throw new RuntimeException('PSN NPSSO has expired. Update PSN_NPSSO in .env.');
        }

        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        $code = $params['code'] ?? null;

        if (!$code) {
            throw new RuntimeException('PSN authorization code not received.');
        }

        return $this->psnExchangeCode($code);
    }

    private function psnExchangeCode(string $code): string
    {
        $res = $this->http()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('09515159-7237-4370-b4f0-315ef5c1ea3f:'),
        ])->asForm()->post('https://ca.account.sony.com/api/authz/v3/oauth/token', [
            'code'         => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => 'com.scee.psxandroid.scecompcall://redirect',
            'token_format' => 'jwt',
        ]);

        if (!$res->successful()) {
            throw new RuntimeException('PSN token exchange failed.');
        }

        return $this->storePsnTokens($res->json());
    }

    private function psnRefresh(string $refreshToken): string
    {
        $res = $this->http()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('09515159-7237-4370-b4f0-315ef5c1ea3f:'),
        ])->asForm()->post('https://ca.account.sony.com/api/authz/v3/oauth/token', [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
            'scope'         => 'psn:mobile.v2.core psn:clientapp',
            'token_format'  => 'jwt',
        ]);

        if (!$res->successful()) {
            throw new RuntimeException('PSN refresh token expired.');
        }

        return $this->storePsnTokens($res->json());
    }

    private function storePsnTokens(array $data): string
    {
        $accessToken  = $data['access_token'];
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn    = $data['expires_in'] ?? 3600;

        Cache::put('psn_access_token', $accessToken, $expiresIn - 60);

        if ($refreshToken) {
            Cache::put('psn_refresh_token', $refreshToken, now()->addDays(58));
        }

        return $accessToken;
    }
}