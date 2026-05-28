@extends('layouts.app')

@section('title', 'Race & Events - XCLusive Racing')

@section('content')
<main class="pt-5 mt-4 pb-5 px-3 min-vh-100 bg-light" x-data="{ platform: null }">
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
                        <div class="small fw-bold text-xcl-purple">12 ACTIVE EVENTS</div>
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
                        <div class="small fw-bold text-xcl-pink">8 ACTIVE EVENTS</div>
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
                        <div class="small fw-bold" style="color:#2563eb">6 ACTIVE EVENTS</div>
                    </button>
                </div>
            </div>

            {{-- CTA --}}
            @guest
            <div class="rounded-3 p-5 text-white text-center bg-gradient-xcl">
                <h2 class="fs-2 fw-black text-uppercase fst-italic mb-3">READY TO RACE?</h2>
                <p class="mb-4 fs-5">Sign up now to access all events and track your ELO rating</p>
                <a href="{{ route('register') }}"
                   class="btn btn-light fw-black text-uppercase px-4 py-2 text-xcl-purple">
                    CREATE PROFILE
                </a>
            </div>
            @endguest
        </div>

        {{-- Platform selected --}}
        <div x-show="platform !== null">
            <button @click="platform = null"
                class="btn btn-link fw-bold text-uppercase text-xcl-purple text-decoration-none mb-4 ps-0">
                ← BACK TO PLATFORMS
            </button>

            <h2 class="display-5 fw-black text-uppercase fst-italic text-dark mb-4">
                <span x-text="platform === 'acc' ? 'ACC CONSOLE' : platform === 'lmu' ? 'LE MANS ULTIMATE' : 'iRACING'"></span>
                EVENTS
            </h2>

            <div class="bg-white rounded-3 shadow-sm p-5 text-center">
                <div class="display-1 mb-3">🏁</div>
                <h3 class="fs-1 fw-black text-uppercase fst-italic text-dark mb-3">COMING SOON</h3>
                <p class="text-secondary fs-5 mb-4">Event system is under development. Check back soon!</p>
                <a href="https://www.xboxcommunityleague.com" target="_blank"
                   class="btn fw-black text-uppercase text-white px-4 py-3"
                   style="background:#7c3aed;">VIEW XCL EVENTS</a>
            </div>
        </div>
    </div>
</main>
@endsection