{{-- Hoofdnavigatiebalk: Bootstrap navbar met Alpine.js hamburger toggle --}}
<nav class="navbar navbar-xcl navbar-expand-md fixed-top" x-data="{ open: false }">
    <div class="container-fluid px-5 align-content-center">

        {{-- Brand: XCLusive Racing logo --}}
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="40">
        </a>

        {{-- Hamburger knop voor mobiele weergave --}}
        <button class="navbar-toggler border-0" type="button" @click="open = !open"
                aria-label="Toggle navigation">
            <svg width="24" height="24" fill="none" stroke="#7c3aed"
                 stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="collapse navbar-collapse" :class="{ 'show': open }">

            {{-- Navigatielinks gecentreerd in de balk --}}
            <ul class="navbar-nav mx-auto gap-md-4">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#about') }}">ABOUT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#teams') }}">TEAMS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/race') }}">XCL EVENTS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#partners') }}">PARTNERS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://raven.gg/stores/xclusive-esports/"
                       target="_blank">MERCHANDISE</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">STATS</a>
                </li>
            </ul>

            {{-- Rechts: Discord knop + authenticatie --}}
            <div class="d-flex align-items-center gap-3">

                {{-- Discord knop --}}
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank"
                   class="btn btn-sm fw-bold text-white d-none d-md-inline-flex bg-xcl-purple"
                   >
                    JOIN DISCORD
                </a>

                @auth
                    {{-- Admin knop alleen voor beheerders --}}
                    @if(auth()->user()->canManage())
                        <a href="{{ route('admin.races.index') }}"
                           class="btn btn-sm fw-bold text-uppercase text-white d-none d-md-inline-flex bg-xcl-purple">
                            ADMIN
                        </a>
                    @endif

                    {{-- Profielpagina met mascot icoon --}}
                    <a href="{{ route('profile') }}"
                       class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <img src="/images/home/brand/xclusive_racing_logo_lion.png"
                             alt="Profile" width="32" height="32" style="object-fit:contain;">
                        PROFILE
                    </a>
                @else
                    {{-- Aanmeldknop voor niet-ingelogde bezoekers --}}
                    <a href="{{ route('register') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple rounded-1 px-4"
                       >
                        SIGN UP
                    </a>

                    {{-- Mascot icoon naast aanmeldknop --}}
                    <img src="/images/home/brand/xclusive_racing_logo_lion.png"
                         alt="XCLusive" width="32" height="32"
                         style="object-fit:contain;">
                @endauth

            </div>
        </div>
    </div>
</nav>