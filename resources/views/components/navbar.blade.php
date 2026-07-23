{{-- Hoofdnavigatiebalk: vanilla JS hamburger toggle + hover dropdowns --}}
<nav class="navbar navbar-xcl navbar-expand-md fixed-top">
    <div class="navbar-xcl__topo" style="background-image:url('/topo.png');"></div>
    <div class="container-fluid px-5 align-content-center">

        {{-- Brand --}}
        <a class="navbar-brand d-none d-md-block" href="{{ url('/') }}">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="40">
        </a>

        {{-- Hamburger --}}
        <button class="navbar-toggler border-0" type="button" aria-label="Toggle navigation">
            <svg width="24" height="24" fill="none" stroke="#7c3aed"
                 stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Mobile logo --}}
        <a href="{{ url('/') }}" class="d-md-none ms-2">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="28">
        </a>

        <div class="collapse navbar-collapse">

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

                <li class="nav-item">
                    <a class="nav-link xcl-nav-news" href="{{ route('news.index') }}">NEWS</a>
                </li>

                {{-- TEAM --}}
                <li class="nav-item position-relative" data-dropdown>
                    <a class="nav-link" href="{{ route('team') }}" data-dropdown-toggle>TEAM</a>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li><a class="xcl-dropdown-item" href="{{ route('teams.pro.index') }}">PROFESSIONAL</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('teams.esports.index') }}">ESPORTS</a></li>
                        <li><a class="xcl-dropdown-item" href="#">STAFF</a></li>
                        <li><a class="xcl-dropdown-item" href="#">JOIN THE TEAM</a></li>
                    </ul>
                </li>

                {{-- XCL EVENTS --}}
                <li class="nav-item position-relative" data-dropdown>
                    <a class="nav-link" href="{{ route('events.index') }}" data-dropdown-toggle>XCL EVENTS</a>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li><a class="xcl-dropdown-item" href="{{ url('/events?type=daily-racing') }}">DAILY RACING</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ url('/events?type=time-trial') }}">TIME TRIALS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('championships.index') }}">CHAMPIONSHIPS</a></li>
                        <li><a class="xcl-dropdown-item" href="{{ route('calendar') }}">CALENDAR</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#partners') }}">PARTNERS</a>
                </li>

                {{-- SHOP --}}
                <li class="nav-item position-relative" data-dropdown>
                    <a class="nav-link" href="#" data-dropdown-toggle>SHOP</a>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
                        <li><a class="xcl-dropdown-item" href="https://raven.gg/stores/xclusive-esports/" target="_blank">MERCHANDISE</a></li>
                        <li><a class="xcl-dropdown-item" href="#">COACHING</a></li>
                        <li><a class="xcl-dropdown-item" href="#">SETUPS</a></li>
                        <li><a class="xcl-dropdown-item" href="#">EVENTS</a></li>
                    </ul>
                </li>

                {{-- RACING --}}
                <li class="nav-item position-relative" data-dropdown>
                    <a class="nav-link" href="#" data-dropdown-toggle>RACING</a>
                    <ul class="xcl-dropdown" data-dropdown-menu
                        style="display:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease">
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
