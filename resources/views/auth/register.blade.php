@extends('layouts.app')

@section('no-sidebar', true)
@section('title', 'Sign Up - XCLusive Racing')

@section('content')
@php
    $startStep = (old('platform') || $steamId) ? 2 : 1;
    $startPlatform = old('platform', $steamId ? 'steam' : '');
@endphp
<div class="xcl-auth-page py-5" data-register>

    <div class="xcl-auth-page__topo" style="background-image:url('/topo.png');"></div>
    <div class="xcl-auth-card">

        <div class="text-center mb-4">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive Racing" height="40" class="mb-3">
        </div>

        {{-- Step 1: Choose platform --}}
        <div data-step="1" style="{{ $startStep === 2 ? 'display:none' : '' }}">
            <h1 class="fs-3 fw-black text-uppercase fst-italic text-white text-center mb-1">Choose Platform</h1>
            <p class="text-white small text-center mb-4">Select the platform you race on</p>

            <div class="d-flex flex-column gap-3">
                <a href="{{ route('auth.steam') }}" class="xcl-platform-btn xcl-platform-btn--steam">
                    <i class="fa-brands fa-steam fs-5"></i>
                    Steam
                </a>
                <button type="button" data-select-platform="xbox" class="xcl-platform-btn xcl-platform-btn--xbox">
                    <i class="fa-brands fa-xbox fs-5"></i>
                    Xbox
                </button>
                <button type="button" data-select-platform="ps5" class="xcl-platform-btn xcl-platform-btn--ps5">
                    <i class="fa-brands fa-playstation fs-5"></i>
                    PlayStation
                </button>
            </div>

            <p class="text-center text-white mt-2" style="color:rgba(255,255,255,.4); font-size:.85rem;">
                Already have an account?
                <a href="{{ route('login') }}" class="fw-bold text-xcl-purple text-decoration-none">Sign in</a>
            </p>
        </div>

        {{-- Step 2: Create profile --}}
        <div data-step="2" style="{{ $startStep === 1 ? 'display:none' : '' }}">
            <h1 class="fs-3 fw-black text-uppercase fst-italic text-white text-center mb-1">Create Profile</h1>
            <p class="text-white-50 small text-center mb-4">Fill in your details to get started</p>

            @if ($errors->any())
            <div class="rounded-3 mb-4 py-2 px-3" style="background:rgba(239,68,68,.15); border-left:3px solid #ef4444;">
                @foreach ($errors->all() as $error)
                    <div class="small text-danger">{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="platform" value="{{ $startPlatform }}" data-platform-value>

                @if ($steamId)
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Steam Account</label>
                    <div class="form-control xcl-auth-input d-flex align-items-center gap-2"
                         style="opacity:.7; cursor:default;">
                        <i class="fa-brands fa-steam"></i>
                        {{ $steamName }}
                    </div>
                </div>
                @else
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">
                        <span data-platform-label>{{ $startPlatform === 'ps5' ? 'PSN Online ID' : ($startPlatform === 'xbox' ? 'Xbox Gamertag' : 'Steam ID or Vanity URL') }}</span>
                    </label>
                    <input type="text" name="gamertag" required
                           data-gamertag-input
                           placeholder="{{ $startPlatform === 'ps5' ? 'Your PSN Online ID' : ($startPlatform === 'xbox' ? 'Your Xbox Gamertag' : 'SteamID64 or custom URL name') }}"
                           value="{{ old('gamertag') }}"
                           class="form-control xcl-auth-input @error('gamertag') is-invalid @enderror">
                    <div data-hint="steam" class="mt-1" style="font-size:.78rem; color:rgba(255,255,255,.3);{{ $startPlatform !== 'steam' ? 'display:none' : '' }}">
                        Enter your 17-digit SteamID64, or the name from steamcommunity.com/id/<strong>name</strong>
                    </div>
                    <div data-hint="xbox" class="mt-1" style="font-size:.78rem; color:rgba(255,255,255,.3);{{ $startPlatform !== 'xbox' ? 'display:none' : '' }}">
                        Enter your Gamertag with or without the #xxxx suffix (e.g. <strong>PlayerName</strong> or <strong>PlayerName#1234</strong>)
                    </div>
                    @error('gamertag')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Email</label>
                    <input type="email" name="email" required placeholder="your@email.com"
                           value="{{ old('email') }}"
                           class="form-control xcl-auth-input @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="form-control xcl-auth-input @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="••••••••"
                           class="form-control xcl-auth-input">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Country</label>
                    <input type="text" name="country" required placeholder="Your country"
                           value="{{ old('country') }}"
                           class="form-control xcl-auth-input @error('country') is-invalid @enderror">
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-white-50 mb-1">Team <span style="color:rgba(255,255,255,.3);">(Optional)</span></label>
                    <input type="text" name="team" placeholder="Your team"
                           value="{{ old('team') }}"
                           class="form-control xcl-auth-input">
                </div>

                <div class="mb-4">
                    <label class="d-flex align-items-start gap-2" style="cursor:pointer">
                        <input type="checkbox" name="privacy_accepted" value="1"
                               {{ old('privacy_accepted') ? 'checked' : '' }}
                               class="mt-1 flex-shrink-0 @error('privacy_accepted') is-invalid @enderror"
                               style="accent-color:#7c3aed;width:16px;height:16px;">
                        <span style="font-size:.85rem;color:rgba(255,255,255,.6);line-height:1.5">
                            I have read and agree to the
                            <a href="{{ route('privacy') }}" target="_blank"
                               class="text-decoration-none fw-bold" style="color:#a78bfa">Privacy Policy</a>
                        </span>
                    </label>
                    @error('privacy_accepted')
                        <div class="small text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-3">
                    <button type="button" data-back
                        class="btn flex-fill fw-black text-uppercase text-white py-3"
                        style="background:rgba(255,255,255,.06); border:1.5px solid rgba(255,255,255,.12); border-radius:10px; letter-spacing:.06em;">
                        Back
                    </button>
                    <button type="submit"
                        class="btn flex-fill fw-black text-uppercase text-white py-3 bg-gradient-xcl border-0"
                        style="border-radius:10px; letter-spacing:.06em;">
                        Create Profile
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
