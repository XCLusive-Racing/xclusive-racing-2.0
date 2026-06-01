@extends('layouts.app')

@section('title', 'Sign Up - XCLusive Racing')

@section('content')
<div class="xcl-page d-flex align-items-center justify-content-center bg-light py-5"
     x-data="{ step: {{ old('platform') ? 2 : 1 }}, platform: '{{ old('platform', '') }}' }">
    <div class="bg-white rounded-3 shadow p-4 p-md-5 w-100" style="max-width:480px">

        {{-- Step 1: Choose platform --}}
        <div x-show="step === 1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-0">CHOOSE PLATFORM</h2>
                <a href="{{ url('/') }}" class="text-secondary text-decoration-none fs-5">&times;</a>
            </div>

            <div class="d-flex flex-column gap-3">
                <button type="button" @click="platform = 'steam'; step = 2"
                    class="btn w-100 py-3 fw-bold text-uppercase text-white d-flex align-items-center justify-content-center gap-2"
                    style="background:#1b2838;">
                    <img src="https://store.cloudflare.steamstatic.com/public/shared/images/responsive/share_steam_logo.png"
                         height="18" alt="Steam"> STEAM
                </button>
                <button type="button" @click="platform = 'xbox'; step = 2"
                    class="btn w-100 py-3 fw-bold text-uppercase text-white d-flex align-items-center justify-content-center gap-2"
                    style="background:#107c10;">
                    <svg height="18" viewBox="0 0 24 24" fill="white"><path d="M4.102 21.033C6.211 22.881 8.977 24 12 24c3.026 0 5.789-1.119 7.902-2.967 1.877-1.912-4.316-8.709-7.902-11.417-3.582 2.708-9.779 9.505-7.898 11.417zm11.16-14.406c2.5 2.961 7.484 10.313 6.076 12.912C23.002 17.38 24 14.812 24 12c0-4.438-2.402-8.319-5.984-10.406.386 1.387-1.449 3.33-2.754 5.033zM5.98 1.594C2.4 3.681 0 7.562 0 12c0 2.812.998 5.38 2.664 7.539-1.408-2.6 3.578-9.951 6.074-12.912-1.303-1.703-3.136-3.646-2.758-5.033zM12 0C8.977 0 6.211 1.119 4.102 2.967 5.127 2.67 9.616 5.979 12 8.033c2.387-2.054 6.876-5.362 7.902-5.066C17.789 1.119 15.026 0 12 0z"/></svg>
                    XBOX
                </button>
                <button type="button" @click="platform = 'ps5'; step = 2"
                    class="btn w-100 py-3 fw-bold text-uppercase text-white d-flex align-items-center justify-content-center gap-2"
                    style="background:#003087;">
                    <svg height="18" viewBox="0 0 24 24" fill="white"><path d="M8.984 2.596v14.347l3.915 1.261V6.688c0-.69.304-1.151.794-.991.636.181.76.814.76 1.501v5.627c2.656 1.191 4.597-.096 4.597-3.898 0-3.93-1.348-5.543-5.851-6.998-.876-.27-2.369-.583-4.215-.333zM.001 19.057c4.368 1.669 9.188 2.374 13.216.724l-1.545-.54v-2.828L6.19 14.842v4.881c0 .82-.159 1.569-1.184 1.206a1.415 1.415 0 0 1-.843-1.3v-3.568L.001 14.576v4.481zm23.999-4.485v2.888l-4.56-1.468v2.036c0 .961-.243 1.711-1.22 1.376L16.785 19v-2.777l-3.569-1.148V17.9l1.56.503c2.287.732 8.283 2.178 9.224-.785v-.046z"/></svg>
                    PLAYSTATION
                </button>
            </div>

            <div class="text-center mt-4">
                <p class="text-secondary small">Already have an account?
                    <a href="{{ route('login') }}" class="fw-bold text-xcl-purple text-decoration-none">Sign in</a>
                </p>
            </div>
        </div>

        {{-- Step 2: Create profile --}}
        <div x-show="step === 2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-0">CREATE PROFILE</h2>
                <a href="{{ url('/') }}" class="text-secondary text-decoration-none fs-5">&times;</a>
            </div>

            @if ($errors->any())
            <div class="alert alert-danger py-2">
                @foreach ($errors->all() as $error)
                    <div class="small">{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="platform" :value="platform">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">
                        <span x-text="platform === 'steam' ? 'Steam ID or Vanity URL' : platform === 'ps5' ? 'PSN Online ID' : 'Xbox Gamertag'"></span>
                    </label>
                    <input type="text" name="gamertag" required
                           :placeholder="platform === 'steam' ? 'SteamID64 or custom URL name' : platform === 'ps5' ? 'Your PSN Online ID' : 'Your Xbox Gamertag'"
                           value="{{ old('gamertag') }}"
                           class="form-control border-secondary @error('gamertag') is-invalid @enderror">
                    <div class="form-text text-muted small" x-show="platform === 'steam'">
                        Enter your 17-digit SteamID64, or the name from steamcommunity.com/id/<strong>name</strong>
                    </div>
                    @error('gamertag')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Email</label>
                    <input type="email" name="email" required placeholder="your@email.com"
                           value="{{ old('email') }}"
                           class="form-control border-secondary @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="form-control border-secondary @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="••••••••"
                           class="form-control border-secondary">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Country</label>
                    <input type="text" name="country" required placeholder="Your country"
                           value="{{ old('country') }}"
                           class="form-control border-secondary @error('country') is-invalid @enderror">
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-dark">Team (Optional)</label>
                    <input type="text" name="team" placeholder="Your team"
                           value="{{ old('team') }}"
                           class="form-control border-secondary">
                </div>

                <div class="d-flex gap-3">
                    <button type="button" @click="step = 1"
                        class="btn flex-fill fw-bold text-uppercase border border-2 border-secondary text-dark">
                        BACK
                    </button>
                    <button type="submit"
                        class="btn flex-fill fw-bold text-uppercase text-white"
                        style="background:#7c3aed;">
                        CREATE PROFILE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection