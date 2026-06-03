@extends('layouts.app')

@section('title', 'Race & Events - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3 bg-light" x-data="{
    platform: null,
    weeksShown: 1,
    selectPlatform(p) { this.platform = p; this.weeksShown = 1; },
    inRange(dateStr) {
        const d = new Date(dateStr);
        const now = new Date(); now.setHours(0,0,0,0);
        const cutoff = new Date(now);
        cutoff.setDate(cutoff.getDate() + this.weeksShown * 7);
        return d >= now && d <= cutoff;
    }
}">
    <div class="container-xl">
        <div class="mb-5 pt-3">
            <h1 class="display-4 fw-black text-uppercase fst-italic text-dark mb-2">RACE & EVENTS</h1>
            <p class="text-secondary fs-5">Choose your platform and find races to join</p>
        </div>

        {{-- Platform cards --}}
        <div x-show="platform === null">
            <div class="d-flex gap-3 mb-5 align-items-end" style="height:460px">
                @foreach([
                    ['acc',     '#7c3aed', 'ACC Console',      'Assetto Corsa Competizione · PS5 &amp; Xbox Series X/S'],
                    ['lmu',     '#db2777', 'Le Mans Ultimate',  'Le Mans Ultimate · Premium PC Sim Racing'],
                    ['iracing', '#2563eb', 'iRacing',           'iRacing · World\'s Leading Online Sim Racing'],
                ] as [$game, $color, $label, $desc])
                @php $count = $races->where('game', $game)->count(); @endphp
                <div x-data="{ on: false }"
                     @mouseenter="on = true;  $refs.vid.play().catch(()=>{})"
                     @mouseleave="on = false; $refs.vid.pause()"
                     @click="selectPlatform('{{ $game }}')"
                     :style="{ height: on ? '460px' : '280px' }"
                     style="flex:1;height:280px;border-radius:16px;overflow:hidden;cursor:pointer;position:relative;transition:height .45s cubic-bezier(.4,0,.2,1)">

                    {{-- Video (swap src for real file when available) --}}
                    <video x-ref="vid" muted loop playsinline preload="none"
                           style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                        <source src="/videos/{{ $game }}.mp4" type="video/mp4">
                    </video>

                    {{-- Placeholder background (visible when no video) --}}
                    <div style="position:absolute;inset:0;background:linear-gradient(160deg,{{ $color }}55 0%,{{ $color }}cc 100%)"></div>

                    {{-- Dark overlay --}}
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.25) 55%,transparent 100%)"></div>

                    {{-- Content --}}
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:1.5rem">

                        {{-- Event count --}}
                        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:{{ $color }};background:rgba(0,0,0,.35);display:inline-block;padding:3px 10px;border-radius:20px;margin-bottom:.6rem">
                            {{ $count }} open {{ $count === 1 ? 'event' : 'events' }}
                        </div>

                        {{-- Title --}}
                        <div style="color:white;font-size:1.45rem;font-weight:900;text-transform:uppercase;font-style:italic;line-height:1.1;margin-bottom:.75rem">
                            {!! $label !!}
                        </div>

                        {{-- Description + CTA — slide in on hover --}}
                        <div :style="on ? 'max-height:120px;opacity:1' : 'max-height:0;opacity:0'"
                             style="overflow:hidden;transition:max-height .35s ease,opacity .3s ease">
                            <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin-bottom:.85rem">{!! $desc !!}</p>
                            <span style="background:{{ $color }};color:white;padding:8px 22px;border-radius:8px;font-weight:800;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;display:inline-block">
                                View Events →
                            </span>
                        </div>
                    </div>

                    {{-- Coloured top accent line --}}
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{ $color }}"></div>
                </div>
                @endforeach
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
            @php $gameRaces = $races->where('game', $game); @endphp
            <div x-show="platform === '{{ $game }}'">

                @if($gameRaces->isEmpty())
                    <div class="bg-white rounded-3 shadow-sm p-5 text-center">
                        <div class="display-1 mb-3">🏁</div>
                        <h3 class="fs-1 fw-black text-uppercase fst-italic text-dark mb-3">NO UPCOMING EVENTS</h3>
                        <p class="text-secondary fs-5">Check back soon for new events!</p>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($gameRaces as $race)
                        <div class="col-md-6 col-lg-4"
                             x-show="inRange('{{ $race->scheduled_at->toDateString() }}')">
                            <div class="bg-white rounded-3 shadow-sm h-100 d-flex flex-column overflow-hidden">
                                @if($race->image)
                                <div style="height:140px;overflow:hidden">
                                    <img src="{{ asset('storage/'.$race->image) }}" alt="{{ $race->title }}" loading="lazy"
                                         style="width:100%;height:100%;object-fit:cover">
                                </div>
                                @endif
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

                    {{-- Load more / Load less --}}
                    <div class="text-center mt-5">
                        <button x-show="weeksShown === 1" @click="weeksShown = 2"
                                class="btn fw-black text-uppercase px-5 py-2"
                                style="background:#f3e8ff;color:#7c3aed;border:2px solid #e9d5ff;border-radius:8px;font-size:.85rem">
                            Load more
                        </button>
                        <button x-show="weeksShown === 2" @click="weeksShown = 1"
                                class="btn fw-black text-uppercase px-5 py-2"
                                style="background:#f3e8ff;color:#7c3aed;border:2px solid #e9d5ff;border-radius:8px;font-size:.85rem">
                            Load less
                        </button>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</main>
@endsection