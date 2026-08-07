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
        return redirect('https://api.xbl.io/app/auth/' . config('services.openxbl.app_id'));
    }

    public function callback()
    {
        $code = request('code');

        if (! $code) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in was cancelled or failed. Please try again.');
        }

        try {
            $tokenRes = Http::timeout(10)
                ->withHeaders([
                    'x-authorization' => config('services.openxbl.api_key'),
                    'Accept'          => 'application/json',
                ])
                ->post('https://api.xbl.io/app/claim', [
                    'code'    => $code,
                    'app_key' => config('services.openxbl.azure_client_id'),
                ]);
        } catch (ConnectionException) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        $accessToken = $tokenRes->json('token')
            ?? $tokenRes->json('access_token')
            ?? $tokenRes->json('userToken')
            ?? $tokenRes->json('key')
            ?? null;

        if (! $accessToken) {
            \Log::warning('Xbox OAuth claim failed', [
                'status' => $tokenRes->status(),
                'body'   => $tokenRes->json() ?? $tokenRes->body(),
            ]);
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        try {
            $res = Http::timeout(10)
                ->withHeaders([
                    'x-authorization' => $accessToken,
                    'Accept'          => 'application/json',
                ])
                ->get('https://api.xbl.io/v2/account');
        } catch (ConnectionException) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        if (! $res->successful()) {
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in failed. Please try again.');
        }

        $data     = $res->json();
        $xuid     = $data['xuid'] ?? $data['id'] ?? null;
        $gamertag = $data['modernGamertag'] ?? $data['gamertag'] ?? $data['displayName'] ?? null;

        if (! $xuid) {
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Could not retrieve Xbox profile. Please try again.']);
        }

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
}