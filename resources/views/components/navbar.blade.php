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
                <li class="nav-item position-relative" x-data="{ dd: false }">
                    <button class="nav-link fw-bold d-flex align-items-center gap-1 border-0 bg-transparent"
                            @click="dd = !dd" @click.outside="dd = false">
                        RACING
                        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                             :style="dd ? 'transform:rotate(180deg);transition:.2s' : 'transition:.2s'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul x-show="dd"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @click="dd = false"
                        class="xcl-dropdown">
                        <li>
                            <a class="xcl-dropdown-item" href="{{ route('drivers.index') }}">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                LEADERBOARD
                            </a>
                        </li>
                    </ul>
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

                    {{-- Profielpagina met avatar --}}
                    <a href="{{ route('profile') }}"
                       class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <x-rank-avatar :user="auth()->user()" :size="32" :badge="false" />
                        PROFILE
                    </a>
                @else
                    {{-- Aanmeldknop voor niet-ingelogde bezoekers --}}
                    <a href="{{ route('login') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple rounded-1 px-4"
                       >
                        SIGN IN
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