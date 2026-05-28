@extends('layouts.app')

@section('title', 'Profile - XCLusive Racing')

@section('content')
<main class="pt-5 mt-4 pb-5 px-3 min-vh-100 bg-light">
    <div class="container" style="max-width:900px">

        {{-- Profile header --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0 bg-gradient-xcl"
                     style="width:96px;height:96px">
                    <span class="display-5 fw-black text-white">{{ strtoupper($user->name[0]) }}</span>
                </div>
                <div>
                    <h1 class="display-6 fw-black text-uppercase fst-italic text-dark mb-1">{{ $user->name }}</h1>
                    <p class="text-secondary text-uppercase mb-1">
                        {{ $user->country }} &bull; {{ strtoupper($user->platform) }}
                    </p>
                    @if($user->team)
                    <p class="fw-bold text-uppercase text-xcl-purple mb-0">{{ $user->team }}</p>
                    @endif
                </div>
            </div>
            <a href="{{ route('profile.edit') }}"
               class="btn fw-black text-uppercase text-white px-4 py-2"
               style="background:#7c3aed;">EDIT PROFILE</a>
        </div>

        {{-- ELO ratings --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="bg-white rounded-3 shadow-sm p-4 elo-card elo-acc">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wide mb-2">ACC CONSOLE</div>
                    <div class="elo-value">{{ $user->elo_acc }}</div>
                    <p class="text-secondary small mb-0">Current Rating</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-3 shadow-sm p-4 elo-card elo-lmu">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wide mb-2">LE MANS ULTIMATE</div>
                    <div class="elo-value">{{ $user->elo_lmu }}</div>
                    <p class="text-secondary small mb-0">Current Rating</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-3 shadow-sm p-4 elo-card elo-iracing">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wide mb-2">iRACING</div>
                    <div class="elo-value">{{ $user->elo_iracing }}</div>
                    <p class="text-secondary small mb-0">Current Rating</p>
                </div>
            </div>
        </div>

        {{-- Next steps --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-4">NEXT STEPS</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="{{ url('/race') }}" class="next-step-card">
                        <div class="next-step-title mb-2">FIND RACES</div>
                        <p>Browse and join upcoming racing events</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="https://www.xboxcommunityleague.com" target="_blank" class="next-step-card">
                        <div class="next-step-title mb-2">XCL EVENTS</div>
                        <p>View all XCL hosted events and championships</p>
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="bg-white rounded-3 shadow-sm p-4">
            <h2 class="fs-2 fw-black text-uppercase fst-italic text-dark mb-4">YOUR STATS</h2>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">0</div>
                        <div class="stat-label">Races</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">0</div>
                        <div class="stat-label">Wins</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">0</div>
                        <div class="stat-label">Podiums</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-num">0%</div>
                        <div class="stat-label">Win Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection