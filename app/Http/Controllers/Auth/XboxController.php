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
        \Log::info('Xbox OAuth callback received', ['params' => request()->all()]);

        $code = request('code');

        if (! $code) {
            \Log::warning('Xbox OAuth callback missing code', ['params' => request()->all()]);
            return redirect()->route('register')
                ->with('xbox_error', 'Xbox sign-in was cancelled or failed. Please try again.');
        }

        // Exchange the authorization code for an access token
        try {
            $tokenRes = Http::timeout(10)
                ->withHeaders([
                    'x-authorization' => config('services.openxbl.api_key'),
                    'Accept'          => 'application/json',
                ])
                ->post('https://api.xbl.io/app/auth/' . config('services.openxbl.app_id') . '/token', [
                    'code' => $code,
                ]);
        } catch (ConnectionException) {
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Could not reach Xbox Live. Please try again.']);
        }

        if (! $tokenRes->successful()) {
            \Log::error('Xbox OAuth token exchange failed', [
                'status' => $tokenRes->status(),
                'body'   => $tokenRes->body(),
            ]);
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Xbox sign-in failed. Please try again.']);
        }

        $accessToken = $tokenRes->json('token') ?? $tokenRes->json('access_token');

        if (! $accessToken) {
            \Log::error('Xbox OAuth missing token in response', ['body' => $tokenRes->json()]);
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Xbox sign-in failed. Please try again.']);
        }

        // Fetch the authenticated user's Xbox profile
        try {
            $res = Http::timeout(10)
                ->withHeaders([
                    'x-authorization' => $accessToken,
                    'Accept'          => 'application/json',
                ])
                ->get('https://api.xbl.io/v2/account');
        } catch (ConnectionException) {
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Could not reach Xbox Live. Please try again.']);
        }

        if (! $res->successful()) {
            \Log::error('Xbox OAuth account fetch failed', [
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Xbox sign-in failed. Please try again.']);
        }

        $data     = $res->json();
        $xuid     = $data['xuid'] ?? $data['id'] ?? null;
        $gamertag = $data['modernGamertag'] ?? $data['gamertag'] ?? $data['displayName'] ?? null;

        if (! $xuid) {
            \Log::error('Xbox OAuth missing xuid', ['body' => $data]);
            return redirect()->route('register')
                ->withErrors(['gamertag' => 'Could not retrieve Xbox profile. Please try again.']);
        }

        $platformId = 'M' . $xuid;

        // Already logged in — just link the account (future feature)
        if (auth()->check()) {
            return redirect()->route('profile');
        }

        // Already registered as a real account — send to login
        $existing = User::where('platform', 'xbox')
            ->where('platform_id', $platformId)
            ->where('email', 'not like', '%@import.local')
            ->first();

        if ($existing) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This Xbox account is already registered. Please sign in instead.']);
        }

        // Store in session and continue to registration form (fill in email/password/country)
        session([
            'xbox_platform_id' => $platformId,
            'xbox_name'        => $gamertag ?? $xuid,
        ]);

        return redirect()->route('register');
    }
}
