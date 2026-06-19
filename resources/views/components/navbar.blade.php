{{-- Hoofdnavigatiebalk: Bootstrap navbar met Alpine.js hamburger toggle --}}
<nav class="navbar navbar-xcl navbar-expand-md fixed-top" x-data="{ open: false }">
    <div class="navbar-xcl__topo" style="background-image:url('/topo.png');"></div>
    <div class="container-fluid px-5 align-content-center">

        {{-- Brand --}}
        <a class="navbar-brand d-none d-md-block" href="{{ url('/') }}">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="40">
        </a>

        {{-- Hamburger --}}
        <button class="navbar-toggler border-0" type="button"
                @click="open = !open; $dispatch('navbar-toggled', { open: open })"
                aria-label="Toggle navigation">
            <svg width="24" height="24" fill="none" stroke="#7c3aed"
                 stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Mobile logo --}}
        <a href="{{ url('/') }}" class="d-md-none ms-2">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="28">
        </a>

        <div class="collapse navbar-collapse" :class="{ 'show': open }">

            {{-- Mobile: Profile + Admin at top of hamburger menu --}}
            @auth
            <div class="d-flex d-md-none align-items-center gap-2 py-2 mb-1 border-bottom border-secondary-subtle">
                <a href="{{ route('profile') }}"
                   class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                    <x-rank-avatar :user="auth()->user()" :size="28" :badge="false" />
                    PROFILE
                </a>
                @if(auth()->user()->canManage())
                <a href="{{ route('admin.races.index') }}"
                   class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple ms-auto">
                    ADMIN
                </a>
                @endif
            </div>
            @endauth

            {{-- Nav links --}}
            <ul class="navbar-nav mx-auto gap-md-4">

                <li class="nav-item d-md-none">
                    <a class="nav-link" href="{{ url('/') }}">HOME</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#about') }}">ABOUT</a>
                </li>

                {{-- TEAM --}}
                <li class="nav-item position-relative"
                    x-data="{ dd: false }"
                    @mouseenter="dd = true"
                    @mouseleave="dd = false"
                    @click.outside="dd = false">
                    <a class="nav-link" href="{{ route('team') }}">TEAM</a>
                    <ul x-show="dd"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="xcl-dropdown">
                        <li><a class="xcl-dropdown-item" href="{{ route('teams.pro.index') }}">PROFESSIONAL</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('teams.esports.index') }}">ESPORTS</a></li>
                        <li><a class="xcl-dropdown-item" href="#">STAFF</a></li>
                        <li><a class="xcl-dropdown-item" href="#">JOIN THE TEAM</a></li>
                    </ul>
                </li>

                {{-- XCL EVENTS --}}
                <li class="nav-item position-relative"
                    x-data="{ dd: false }"
                    @mouseenter="dd = true"
                    @mouseleave="dd = false"
                    @click.outside="dd = false">
                    <a class="nav-link" href="#">XCL EVENTS</a>
                    <ul x-show="dd"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="xcl-dropdown">
                        <li><a class="xcl-dropdown-item" href="{{ route('events.index') }}">EVENTS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('calendar') }}">CALENDAR</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#partners') }}">PARTNERS</a>
                </li>

                {{-- SHOP --}}
                <li class="nav-item position-relative"
                    x-data="{ dd: false }"
                    @mouseenter="dd = true"
                    @mouseleave="dd = false"
                    @click.outside="dd = false">
                    <a class="nav-link" href="#">SHOP</a>
                    <ul x-show="dd"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="xcl-dropdown">
                        <li><a class="xcl-dropdown-item" href="https://raven.gg/stores/xclusive-esports/" target="_blank">MERCHANDISE</a></li>
                        <li><a class="xcl-dropdown-item" href="#">COACHING</a></li>
                        <li><a class="xcl-dropdown-item" href="#">SETUPS</a></li>
                        <li><a class="xcl-dropdown-item" href="#">EVENTS</a></li>
                    </ul>
                </li>

                {{-- RACING --}}
                <li class="nav-item position-relative"
                    x-data="{ dd: false }"
                    @mouseenter="dd = true"
                    @mouseleave="dd = false"
                    @click.outside="dd = false">
                    <a class="nav-link" href="#">RACING</a>
                    <ul x-show="dd"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="xcl-dropdown">
                        <li><a class="xcl-dropdown-item" href="{{ route('championships.index') }}">CHAMPIONSHIPS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('drivers.index') }}">LEADERBOARD</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('results.index') }}">RESULTS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('bop.index') }}">BOPs</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('reports.index') }}">REPORTS</a></li>
                    </ul>
                </li>

            </ul>

            {{-- Right: Discord + auth (desktop only) --}}
            <div class="d-none d-md-flex align-items-center gap-3">

                <a href="{{ config('xcl.discord_url') }}" target="_blank"
                   class="btn btn-sm fw-bold text-white d-none d-md-inline-flex bg-xcl-purple">
                    JOIN DISCORD
                </a>

                @auth
                    @if(auth()->user()->canManage())
                        <a href="{{ route('admin.races.index') }}"
                           class="btn btn-sm fw-bold text-uppercase text-white d-none d-md-inline-flex bg-xcl-purple">
                            ADMIN
                        </a>
                    @endif

                    <a href="{{ route('profile') }}"
                       class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <x-rank-avatar :user="auth()->user()" :size="32" :badge="false" />
                        PROFILE
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple rounded-1 px-4">
                        SIGN IN
                    </a>

                    <img src="/images/home/brand/xclusive_racing_logo_lion.png"
                         alt="XCLusive" width="32" height="32"
                         style="object-fit:contain;">
                @endauth

            </div>
        </div>
    </div>
</nav>
