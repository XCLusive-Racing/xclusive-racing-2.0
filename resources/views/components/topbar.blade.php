{{-- Topbar: vaste platformbalk bovenaan de pagina (36px hoogte) --}}
{{-- Desktop: PLAY ON label + game badges | platform badges + PLATFORM label --}}
{{-- Mobile: game icons only (no label) | Sign In (guests) + platform icons --}}
<div class="xcl-topbar d-flex align-items-center gap-2 px-4">

    {{-- ── PLAY ON game badges — icons shown on both mobile & desktop, label desktop-only ── --}}
    <span class="xcl-topbar-label d-none d-md-inline">PLAY ON</span>

    <div class="xcl-topbar-games d-flex align-items-center">
        {{-- ACC --}}
        <a href="https://www.assettocorsa.net/competizione/" target="_blank"
           class="xcl-game-badge xcl-game-acc" title="Assetto Corsa Competizione">
            <span class="xcl-badge-default">
                <img src="/images/home/icons/ACC Logo.png" class="xcl-badge-icon" alt="ACC">
                <span class="xcl-badge-text">ACC</span>
            </span>
            <img src="/images/home/logos/ACC-logo.png" class="xcl-badge-hover" alt="ACC">
        </a>

        {{-- Le Mans Ultimate --}}
        <a href="https://www.lemansultimate.com/" target="_blank"
           class="xcl-game-badge xcl-game-lmu" title="Le Mans Ultimate">
            <span class="xcl-badge-default">
                <img src="/images/home/icons/LM Logo.png" class="xcl-badge-icon" alt="LMU">
                <span class="xcl-badge-text">LMU</span>
            </span>
            <img src="/images/home/logos/LeMans-Logo.png" class="xcl-badge-hover" alt="Le Mans Ultimate">
        </a>

        {{-- iRacing --}}
        <a href="https://www.iracing.com/" target="_blank"
           class="xcl-game-badge xcl-game-iracing" title="iRacing">
            <span class="xcl-badge-default">
                <img src="/images/home/icons/iR Logo.png" class="xcl-badge-icon" alt="iRacing">
                <span class="xcl-badge-text">iRACING</span>
            </span>
            <img src="/images/home/logos/iracing-logo-white.png" class="xcl-badge-hover" alt="iRacing">
        </a>

        {{-- AC Rally --}}
        <a href="#" class="xcl-game-badge xcl-game-acrally" title="AC Rally">
            <span class="xcl-badge-default">
                <img src="/images/home/icons/AC R Logo.png" class="xcl-badge-icon" alt="AC Rally">
                <span class="xcl-badge-text">AC RALLY</span>
            </span>
            <img src="/images/home/logos/ACRally-logo.png" class="xcl-badge-hover" alt="AC Rally">
        </a>
    </div>

    {{-- ── Desktop only: platform icon badges + PLATFORM label ───────────────── --}}
    <div class="xcl-topbar-platform d-none d-md-flex">
        <div class="xcl-platform-badge">
            <img src="/images/platforms/steam.png" class="xcl-platform-icon" alt="Steam">
            <span class="xcl-platform-label">Steam</span>
        </div>
        <div class="xcl-platform-badge">
            <img src="/images/platforms/xbox.png" class="xcl-platform-icon" alt="Xbox">
            <span class="xcl-platform-label">Xbox S/X</span>
        </div>
        <div class="xcl-platform-badge">
            <img src="/images/platforms/ps5.png" class="xcl-platform-icon" alt="PS5">
            <span class="xcl-platform-label">Playstation 5</span>
        </div>
        <span class="xcl-topbar-label">PLATFORM</span>
    </div>

    {{-- ── Mobile only: Sign In link (guests) + platform icons ─────────────────── --}}
    {{-- "MY PROFILE" moved to the hamburger menu — see navbar.blade.php mobile block --}}
    <div class="d-flex d-md-none align-items-center justify-content-between w-100">
        @guest
            <a href="{{ route('login') }}"
               class="btn btn-sm fw-bold text-uppercase text-white rounded-1 px-3"
               style="background:rgba(124,58,237,.8);font-size:.7rem;padding-top:2px;padding-bottom:2px">
                SIGN IN
            </a>
        @endguest
        <div class="d-flex align-items-center gap-1 ms-auto">
            <div class="xcl-platform-badge">
                <img src="/images/platforms/steam.png" class="xcl-platform-icon" alt="Steam">
            </div>
            <div class="xcl-platform-badge">
                <img src="/images/platforms/xbox.png" class="xcl-platform-icon" alt="Xbox">
            </div>
            <div class="xcl-platform-badge">
                <img src="/images/platforms/ps5.png" class="xcl-platform-icon" alt="PS5">
            </div>
        </div>
    </div>

</div>