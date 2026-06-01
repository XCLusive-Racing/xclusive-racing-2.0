<div class="xcl-topbar d-flex align-items-center justify-content-start gap-3 px-4">
    <span class="xcl-topbar-label">PLAY ON</span>

    <a href="https://www.assettocorsa.net/competizione/" target="_blank"
       class="xcl-game-badge xcl-game-acc" title="Assetto Corsa Competizione">
        <span class="xcl-badge-default">
            ACC
            <span class="xcl-badge-placeholder"></span>
        </span>
        <img src="/images/home/ACC-logo.png" class="xcl-badge-hover" alt="ACC">
    </a>

    <a href="https://www.lemansultimate.com/" target="_blank"
       class="xcl-game-badge xcl-game-lmu" title="Le Mans Ultimate">
        <span class="xcl-badge-default">
            LMU
            <span class="xcl-badge-placeholder"></span>
        </span>
        <img src="/images/home/LeMans-Logo.png" class="xcl-badge-hover" alt="Le Mans Ultimate">
    </a>

    <a href="https://www.iracing.com/" target="_blank"
       class="xcl-game-badge xcl-game-iracing" title="iRacing">
        <span class="xcl-badge-default">
            iRACING
            <span class="xcl-badge-placeholder"></span>
        </span>
        <img src="/images/home/iracing-logo-white.png" class="xcl-badge-hover" alt="iRacing">
    </a>

    <a href="#" class="xcl-game-badge xcl-game-acrally" title="AC Rally">
        <span class="xcl-badge-default">
            AC RALLY
            <span class="xcl-badge-placeholder"></span>
        </span>
        <img src="/images/home/ACRally-logo.png" class="xcl-badge-hover" alt="AC Rally">
    </a>
</div>

<nav class="navbar navbar-xcl navbar-expand-md fixed-top" x-data="{ open: false }">
    <div class="container-fluid px-5 align-content-center">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="/images/home/xclusive_racing_logo.png" alt="XCLusive" height="40">
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
                <li class="nav-item"><a class="nav-link" href="#">STATS</a></li>
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
                        <img src="/images/home/xclusive_racing_logo_lion.png"
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