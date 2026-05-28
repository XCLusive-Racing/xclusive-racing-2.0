@extends('layouts.app')

@section('title', 'Race & Events - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3 bg-light" x-data="{ platform: null }">
    <div class="container-xl">
        <div class="mb-5 pt-3">
            <h1 class="display-4 fw-black text-uppercase fst-italic text-dark mb-2">RACE & EVENTS</h1>
            <p class="text-secondary fs-5">Choose your platform and find races to join</p>
        </div>

        {{-- Platform cards --}}
        <div x-show="platform === null">
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <button @click="platform = 'acc'"
                        class="w-100 text-start p-4 rounded-3 border border-2 bg-white h-100"
                        style="border-color:#7c3aed !important; transition:box-shadow .2s"
                        @mouseenter="$el.style.boxShadow='0 4px 20px rgba(124,58,237,.15)'"
                        @mouseleave="$el.style.boxShadow='none'">
                        <div class="fs-3 fw-black text-uppercase fst-italic text-xcl-purple mb-2">ACC CONSOLE</div>
                        <p class="text-secondary mb-3">Assetto Corsa Competizione on PlayStation 5 &amp; Xbox Series X/S</p>
                        <div class="small fw-bold text-xcl-purple">
                            {{ $races->where('game', 'acc')->count() }} OPEN EVENTS
                        </div>
                    </button>
                </div>
                <div class="col-md-4">
                    <button @click="platform = 'lmu'"
                        class="w-100 text-start p-4 rounded-3 border border-2 bg-white h-100"
                        style="border-color:#db2777 !important; transition:box-shadow .2s"
                        @mouseenter="$el.style.boxShadow='0 4px 20px rgba(219,39,119,.15)'"
                        @mouseleave="$el.style.boxShadow='none'">
                        <div class="fs-3 fw-black text-uppercase fst-italic text-xcl-pink mb-2">LE MANS ULTIMATE</div>
                        <p class="text-secondary mb-3">Le Mans Ultimate - Premium PC Sim Racing</p>
                        <div class="small fw-bold text-xcl-pink">
                            {{ $races->where('game', 'lmu')->count() }} OPEN EVENTS
                        </div>
                    </button>
                </div>
                <div class="col-md-4">
                    <button @click="platform = 'iracing'"
                        class="w-100 text-start p-4 rounded-3 border border-2 bg-white h-100"
                        style="border-color:#2563eb !important; transition:box-shadow .2s"
                        @mouseenter="$el.style.boxShadow='0 4px 20px rgba(37,99,235,.15)'"
                        @mouseleave="$el.style.boxShadow='none'">
                        <div class="fs-3 fw-black text-uppercase fst-italic mb-2" style="color:#2563eb">iRACING</div>
                        <p class="text-secondary mb-3">iRacing - World's Leading Online Racing Simulation</p>
                        <div class="small fw-bold" style="color:#2563eb">
                            {{ $races->where('game', 'iracing')->count() }} OPEN EVENTS
                        </div>
                    </button>
                </div>
            </div>

            @guest
            <div class="rounded-3 p-5 text-white text-center bg-gradient-xcl">
                <h2 class="fs-2 fw-black text-uppercase fst-italic mb-3">READY TO RACE?</h2>
                <p class="mb-4 fs-5">Sign up now to access all events and track your ELO rating</p>
                <a href="{{ route('register') }}" class="btn btn-light fw-black text-uppercase px-4 py-2 text-xcl-purple">
                    CREATE PROFILE
                </a>
            </div>
            @endguest
        </div>

        {{-- Platform selected: show races --}}
        <div x-show="platform !== null" x-cloak>
            <button @click="platform = null"
                class="btn btn-link fw-bold text-uppercase text-xcl-purple text-decoration-none mb-4 ps-0">
                ← BACK TO PLATFORMS
            </button>

            <h2 class="display-5 fw-black text-uppercase fst-italic text-dark mb-4">
                <span x-text="platform === 'acc' ? 'ACC CONSOLE' : platform === 'lmu' ? 'LE MANS ULTIMATE' : 'iRACING'"></span>
                EVENTS
            </h2>

            @foreach(['acc', 'lmu', 'iracing'] as $game)
            <div x-show="platform === '{{ $game }}'">
                @php $gameRaces = $races->where('game', $game); @endphp

                @if($gameRaces->isEmpty())
                    <div class="bg-white rounded-3 shadow-sm p-5 text-center">
                        <div class="display-1 mb-3">🏁</div>
                        <h3 class="fs-1 fw-black text-uppercase fst-italic text-dark mb-3">NO UPCOMING EVENTS</h3>
                        <p class="text-secondary fs-5">Check back soon for new events!</p>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($gameRaces as $race)
                        <div class="col-md-6 col-lg-4">
                            <div class="bg-white rounded-3 shadow-sm h-100 d-flex flex-column overflow-hidden">
                                <div class="p-1" style="background: {{ $race->gameColor() }}"></div>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge text-white fw-bold text-uppercase"
                                              style="background:{{ $race->gameColor() }}">
                                            {{ $race->gameLabel() }}
                                        </span>
                                        <span class="badge {{ $race->status === 'open' ? 'bg-success' : 'bg-secondary' }} text-uppercase">
                                            {{ $race->status }}
                                        </span>
                                    </div>
                                    <h3 class="fw-black text-uppercase fst-italic text-dark fs-5 mb-1">{{ $race->title }}</h3>
                                    <p class="text-secondary small mb-1">{{ $race->track }}</p>
                                    <p class="text-secondary small mb-3">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" class="me-1">
                                            <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                                        </svg>
                                        {{ $race->scheduledAtUk()->format('D d M Y · H:i T') }}
                                    </p>
                                    <p class="text-secondary small mb-3">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" class="me-1">
                                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                        </svg>
                                        {{ $race->registrations_count }}{{ $race->max_drivers ? '/' . $race->max_drivers : '' }} registered
                                    </p>
                                    <div class="mt-auto">
                                        <a href="{{ route('race.show', $race) }}"
                                           class="btn fw-black text-uppercase text-white w-100"
                                           style="background:{{ $race->gameColor() }}">
                                            VIEW EVENT
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</main>
@endsection