<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // Pages a visitor can come from that make no sense to land back on after logging in.
    private const NO_RETURN_PATHS = ['login', 'register', 'forgot-password', 'reset-password', 'password', 'auth'];

    public function create(Request $request)
    {
        // A "log in to see/do this" link anywhere on the site: back to that page after
        // logging in. Always the latest page, so an abandoned login earlier doesn't win.
        $previous = url()->previous();
        $path = trim((string) parse_url($previous, PHP_URL_PATH), '/');

        if (parse_url($previous, PHP_URL_HOST) === $request->getHost()
            && ! in_array(explode('/', $path)[0], self::NO_RETURN_PATHS, true)) {
            $request->session()->put('url.intended', $previous);
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('profile'));
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.']);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
