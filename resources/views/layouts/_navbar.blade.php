<div class="xcl-topbar d-flex align-items-center justify-content-start gap-3 px-4">
    <span class="xcl-topbar-label">PLAY ON</span>

    <a href="https://www.assettocorsa.net/competizione/" target="_blank"
       class="xcl-game-badge xcl-game-acc" title="Assetto Corsa Competizione">
        <span class="xcl-badge-default">
            <img src="/images/home/icons/ACC_logo.png" class="xcl-badge-icon" alt="ACC">
            ACC
        </span>
        <img src="/images/home/logos/ACC-logo.png" class="xcl-badge-hover" alt="ACC">
    </a>

    <a href="https://www.lemansultimate.com/" target="_blank"
       class="xcl-game-badge xcl-game-lmu" title="Le Mans Ultimate">
        <span class="xcl-badge-default">
            <img src="/images/home/icons/LMU_Logo.png" class="xcl-badge-icon" alt="LMU">
            LMU
        </span>
        <img src="/images/home/logos/LeMans-Logo.png" class="xcl-badge-hover" alt="Le Mans Ultimate">
    </a>

    <a href="https://www.iracing.com/" target="_blank"
       class="xcl-game-badge xcl-game-iracing" title="iRacing">
        <span class="xcl-badge-default">
            <img src="/images/home/icons/Iracing_logo.png" class="xcl-badge-icon" alt="iRacing">
            iRACING
        </span>
        <img src="/images/home/logos/iracing-logo-white.png" class="xcl-badge-hover" alt="iRacing">
    </a>

    <a href="#" class="xcl-game-badge xcl-game-acrally" title="AC Rally">
        <span class="xcl-badge-default">
            <img src="/images/home/icons/AC_rally_logo.png" class="xcl-badge-icon" alt="AC Rally">
            AC RALLY
        </span>
        <img src="/images/home/logos/ACRally-logo.png" class="xcl-badge-hover" alt="AC Rally">
    </a>
</div>

<nav class="navbar navbar-xcl navbar-expand-md fixed-top" x-data="{ open: false }">
    <div class="container-fluid px-5 align-content-center">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="/images/home/brand/xclusive_racing_logo.png" alt="XCLusive" height="40">
        </a>

        <button class="navbar-toggler border-0" @click="open = !open">
            <svg width="24" height="24" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="collapse navbar-collapse" :class="{ 'show': open }">
            <ul class="navbar-nav mx-auto gap-md-5">
                <li class="nav-item"><a class="nav-link" href="{{ url('/#about') }}">ABOUT</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/#teams') }}">TEAMS</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/race') }}">XCL EVENTS</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/#partners') }}">PARTNERS</a></li>
                <li class="nav-item"><a class="nav-link" href="https://raven.gg/stores/xclusive-esports/" target="_blank">MERCHANDISE</a></li>
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
                                RESULTS
                            </a>
                        </li>
                        <li>
                            <a class="xcl-dropdown-item" href="{{ route('hotlaps.index') }}">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                HOTLAPS
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank"
                   class="btn btn-sm fw-bold text-white d-none d-md-inline-flex bg-xcl-purple">JOIN DISCORD</a>

                @auth
                    @if(auth()->user()->canManage())
                    <a href="{{ route('admin.races.index') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white d-none d-md-inline-flex bg-xcl-purple">
                        ADMIN
                    </a>
                    @endif
                    <a href="{{ route('profile') }}"
                       class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <img src="/images/home/brand/xclusive_racing_logo_lion.png"
                             alt="Profile" width="24" height="24" style="object-fit:contain;">
                        PROFILE
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="btn btn-sm fw-bold text-uppercase text-white bg-xcl-purple rounded-1 px-4">
                        SIGN UP
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>