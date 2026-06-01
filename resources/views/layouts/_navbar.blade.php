<div class="xcl-topbar d-flex align-items-center justify-content-start gap-3 px-4">
    <span class="xcl-topbar-label">GO TO</span>
    <a href="https://www.assettocorsa.net/competizione/" target="_blank" class="xcl-game-badge xcl-game-acc" title="Assetto Corsa Competizione">
        ACC
        <img src="/images/home/ACC.jpg" alt="ACC" width="18" height="18" style="border-radius:3px;object-fit:cover;">
    </a>
    <a href="https://www.lemansultimate.com/" target="_blank" class="xcl-game-badge xcl-game-lmu" title="Le Mans Ultimate">
        LMU
        <img src="/images/home/LMU.png" alt="LMU" width="18" height="18" style="border-radius:3px;object-fit:cover;">
    </a>
    <a href="https://www.iracing.com/" target="_blank" class="xcl-game-badge xcl-game-iracing" title="iRacing">
        iRACING
        <img src="/images/home/Iracing.png" alt="iRacing" width="18" height="18" style="border-radius:3px;object-fit:cover;">
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
            <ul class="navbar-nav mx-auto gap-md-5 fs-5">
                <li class="nav-item"><a class="nav-link" href="{{ url('/#about') }}">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/race') }}">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('') }}">Results</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('') }}">Rating</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/#partners') }}">Partners</a></li>
                <li class="nav-item"><a class="nav-link" href="https://raven.gg/stores/xclusive-esports/" target="_blank">Merchandise</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank"
                   class="btn btn-sm fw-bold text-white d-none d-md-inline-flex"
                   style="background:#7c3aed;">JOIN DISCORD</a>

                @auth
                    @if(auth()->user()->canManage())
                    <a href="{{ route('admin.races.index') }}" class="btn btn-sm fw-bold text-uppercase d-none d-md-inline-flex"
                       style="background:#7c3aed;color:white;">
                        ADMIN
                    </a>
                    @endif
                    <a href="{{ route('profile') }}" class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        PROFILE
                    </a>
                @else
                    <a href="{{ route('login') }}" class="d-flex align-items-center gap-2 text-decoration-none fw-bold text-xcl-purple">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        PROFILE
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>