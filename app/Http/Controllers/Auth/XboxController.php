<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class XboxController extends Controller
{
    public function redirect()
    {
        $params = http_build_query([
            'client_id'     => config('services.openxbl.azure_client_id'),
            'response_type' => 'code',
            'redirect_uri'  => config('services.openxbl.redirect'),
            'scope'         => 'XboxLive.signin XboxLive.offline_access',
            'response_mode' => 'query',
        ]);

        return redirect('https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize?' . $params);
    }

    public function setupRedirect()
    {
        session(['xbox_system_setup' => true]);
        return $this->redirect();
    }

    public function callback()
    {
        $code = request('code');

        if (! $code) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in was cancelled or failed. Please try again.');
        }

        // Exchange auth code for access_token + refresh_token
        try {
            $tokenRes = Http::asForm()->timeout(15)->post(
                'https://login.microsoftonline.com/consumers/oauth2/v2.0/token',
                [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'redirect_uri'  => config('services.openxbl.redirect'),
                    'client_id'     => config('services.openxbl.azure_client_id'),
                    'client_secret' => config('services.openxbl.azure_client_secret'),
                ]
            );
        } catch (ConnectionException) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        $accessToken  = $tokenRes->json('access_token');
        $refreshToken = $tokenRes->json('refresh_token');

        if (! $accessToken) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        // Admin one-time setup: capture system refresh token
        if (session('xbox_system_setup')) {
            session()->forget('xbox_system_setup');
            return $this->handleSystemSetup($refreshToken, $accessToken);
        }

        // Normal registration flow
        $tokenData = $this->getXblChain($accessToken);

        if (! $tokenData) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        [$xuid, $gamertag] = $tokenData;

        $platformId = 'M' . $xuid;

        if (auth()->check()) {
            return redirect()->route('profile');
        }

        $existing = User::where('platform', 'xbox')
            ->where('platform_id', $platformId)
            ->where('email', 'not like', '%@import.local')
            ->first();

        if ($existing) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This Xbox account is already registered. Please sign in instead.']);
        }

        session([
            'xbox_platform_id' => $platformId,
            'xbox_name'        => $gamertag ?? $xuid,
        ]);

        return redirect()->route('register');
    }

    private function handleSystemSetup(?string $refreshToken, string $accessToken): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'        => 'success',
            'refresh_token' => $refreshToken,
            'instruction'   => 'Copy the refresh_token above and set it as XBOX_SYSTEM_REFRESH_TOKEN in your server environment variables.',
        ]);
    }

    // Exchange a Microsoft access token → XBL → XSTS → [xuid, gamertag]
    public function getXblChain(string $accessToken): ?array
    {
        try {
            $xblRes = Http::timeout(15)
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
            $xstsRes = Http::timeout(15)
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
        $xuid      = $xstsRes->json('DisplayClaims.xui.0.xid');

        if (! $xstsToken || ! $xuid) return null;

        // Get gamertag
        try {
            $profileRes = Http::timeout(10)
                ->withHeaders([
                    'Authorization'          => "XBL3.0 x={$userHash};{$xstsToken}",
                    'Accept'                 => 'application/json',
                    'x-xbl-contract-version' => '2',
                ])
                ->get('https://profile.xboxlive.com/users/me/profile/settings', [
                    'settings' => 'ModernGamertag,Gamertag',
                ]);

            $settings = collect($profileRes->json('profileUsers.0.settings') ?? [])
                ->keyBy('id');

            $gamertag = $settings->get('ModernGamertag')['value']
                ?? $settings->get('Gamertag')['value']
                ?? null;
        } catch (\Throwable) {
            $gamertag = null;
        }

        return [$xuid, $gamertag];
    }
}