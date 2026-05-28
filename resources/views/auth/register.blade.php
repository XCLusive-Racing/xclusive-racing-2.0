@extends('layouts.app')

@section('title', 'Sign Up - XCLusive Racing')

@section('content')
<div class="xcl-page d-flex align-items-center justify-content-center bg-light py-5" x-data="{ step: 1, platform: '' }">
    <div class="bg-white rounded-3 shadow p-4 p-md-5 w-100" style="max-width:480px">

        {{-- Step 1: Choose platform --}}
        <div x-show="step === 1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-0">CHOOSE PLATFORM</h2>
                <a href="{{ url('/') }}" class="text-secondary text-decoration-none fs-5">&times;</a>
            </div>

            <div class="d-flex flex-column gap-3">
                <button @click="platform = 'steam'; step = 2"
                    class="btn w-100 py-3 fw-bold text-uppercase border border-2"
                    style="border-color:#7c3aed !important; color:#7c3aed">
                    🖥️ STEAM
                </button>
                <button @click="platform = 'ps5'; step = 2"
                    class="btn w-100 py-3 fw-bold text-uppercase border border-2"
                    style="border-color:#2563eb !important; color:#2563eb">
                    🎮 PLAYSTATION 5
                </button>
                <button @click="platform = 'xbox'; step = 2"
                    class="btn w-100 py-3 fw-bold text-uppercase border border-2"
                    style="border-color:#16a34a !important; color:#16a34a">
                    🎮 XBOX SERIES X/S
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
                        <span x-text="platform === 'steam' ? 'Steam ID' : platform === 'ps5' ? 'PSN Username' : 'Xbox Gamertag'"></span>
                    </label>
                    <input type="text" name="platform_id" required
                           :placeholder="platform === 'steam' ? 'Your Steam ID' : 'Your username'"
                           value="{{ old('platform_id') }}"
                           class="form-control border-secondary">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Display Name</label>
                    <input type="text" name="name" required placeholder="Your display name"
                           value="{{ old('name') }}"
                           class="form-control border-secondary">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Email</label>
                    <input type="email" name="email" required placeholder="your@email.com"
                           value="{{ old('email') }}"
                           class="form-control border-secondary">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-dark">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="form-control border-secondary">
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
                           class="form-control border-secondary">
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