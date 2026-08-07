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

        // Exchange the authorization code for a user token via OpenXBL
        $debug = ['code_preview' => substr($code, 0, 40) . '...'];

        // Exchange the authorization code for a user token via /app/claim
        $usedEndpoint = 'POST /app/claim';
        try {
            $tokenRes = Http::timeout(10)
                ->withHeaders([
                    'x-authorization' => config('services.openxbl.api_key'),
                    'Accept'          => 'application/json',
                ])
                ->post('https://api.xbl.io/app/claim', [
                    'code'   => $code,
                    'app_id' => config('services.openxbl.app_id'),
                ]);
        } catch (ConnectionException) {
            return response()->json(['debug' => 'token exchange connection failed']);
        }

        $debug['exchange_endpoint'] = $usedEndpoint;
        $debug['exchange_status']   = $tokenRes?->status();
        $debug['exchange_body']     = $tokenRes?->json() ?? $tokenRes?->body();

        $accessToken = $tokenRes->json('token')
            ?? $tokenRes->json('access_token')
            ?? $tokenRes->json('userToken')
            ?? null;

        if (! $accessToken) {
            return response()->json($debug);
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
            return response()->json(['debug' => 'account fetch connection failed']);
        }

        $debug['account_status'] = $res->status();
        $debug['account_body']   = $res->json() ?? $res->body();

        if (! $res->successful()) {
            return response()->json($debug);
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
