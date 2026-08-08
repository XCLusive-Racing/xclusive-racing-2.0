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

    // ── Xbox (Xbox Live direct) ───────────────────────────────────────────────

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

        $xblAuth = $this->getXblAuthFromSystemToken();

        if (! $xblAuth) {
            throw new RuntimeException(
                'Xbox lookup is temporarily unavailable. Please enter your XUID instead ' .
                '(the 15–16 digit number from your Xbox profile page at xbox.com).'
            );
        }

        [$userHash, $xstsToken] = $xblAuth;

        try {
            $res = $this->http()
                ->withHeaders([
                    'Authorization'          => "XBL3.0 x={$userHash};{$xstsToken}",
                    'Accept'                 => 'application/json',
                    'x-xbl-contract-version' => '2',
                ])
                ->get('https://profile.xboxlive.com/users/gt(' . rawurlencode($baseTag) . ')/profile/settings', [
                    'settings' => 'ModernGamertag,Gamertag',
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach Xbox. Please try again or enter your XUID.');
        }

        $profile = $res->json('profileUsers.0');

        if (! $profile || ! ($profile['id'] ?? null)) {
            throw new RuntimeException(
                'Xbox account not found for "' . $baseTag . '". ' .
                'Enter your current gamertag exactly as shown on your Xbox profile. ' .
                'If your gamertag recently changed or contains spaces, enter your XUID instead (the 15–16 digit number from your Xbox profile page).'
            );
        }

        $settings = collect($profile['settings'] ?? [])->keyBy('id');
        $gamertag = $settings->get('ModernGamertag')['value']
            ?? $settings->get('Gamertag')['value']
            ?? $baseTag;

        return [
            'platform_id' => 'M' . $profile['id'],
            'name'        => $gamertag,
        ];
    }

    private function getXblAuthFromSystemToken(): ?array
    {
        $refreshToken = config('services.openxbl.system_refresh_token');
        if (! $refreshToken) return null;

        try {
            $tokenRes = $this->http()->asForm()->post(
                'https://login.microsoftonline.com/consumers/oauth2/v2.0/token',
                [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => config('services.openxbl.azure_client_id'),
                    'client_secret' => config('services.openxbl.azure_client_secret'),
                    'scope'         => 'XboxLive.signin XboxLive.offline_access',
                ]
            );
        } catch (ConnectionException) {
            return null;
        }

        $accessToken = $tokenRes->json('access_token');
        if (! $accessToken) return null;

        try {
            $xblRes = $this->http()
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post('https://user.auth.xboxlive.com/user/authenticate', [
                    'Properties'   => [
                        'AuthMethod' => 'RPS',
                        'SiteName'   => 'user.auth.xboxlive.com',
                        'RpsTicket'  => 'd=' . $accessToken,
                    ],
                    'RelyingParty' => 'http://auth.xboxlive.com',
                    'TokenType'    => 'JWT',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        $xblToken = $xblRes->json('Token');
        if (! $xblToken) return null;

        try {
            $xstsRes = $this->http()
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post('https://xsts.auth.xboxlive.com/xsts/authorize', [
                    'Properties'   => [
                        'SandboxId'  => 'RETAIL',
                        'UserTokens' => [$xblToken],
                    ],
                    'RelyingParty' => 'http://xboxlive.com',
                    'TokenType'    => 'JWT',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        $xstsToken = $xstsRes->json('Token');
        $userHash  = $xstsRes->json('DisplayClaims.xui.0.uhs');

        if (! $xstsToken || ! $userHash) return null;

        return [$userHash, $xstsToken];
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