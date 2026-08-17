{{-- Topbar: vaste platformbalk bovenaan de pagina (36px hoogte) --}}
{{-- Desktop: PLAY ON label + game badges | platform badges + PLATFORM label --}}
{{-- Mobile: game icons only (no label) | Sign In (guests) + platform icons --}}
<div class="xcl-topbar d-flex align-items-center gap-2 px-4">

    {{-- ── PLAY ON game badges — icons shown on both mobile & desktop, label desktop-only ── --}}
    <span class="xcl-topbar-label d-none d-md-inline">PLAY ON</span>

    <div class="xcl-topbar-games d-flex align-items-center">
        {{-- ACC --}}
        <div class="play-on-item">
            <a href="https://www.assettocorsa.net/competizione/" target="_blank"
               class="xcl-game-badge xcl-game-acc">
                <img src="/images/home/icons/ACC Logo.png" class="xcl-badge-icon" alt="ACC">
                <span class="xcl-badge-text">ACC</span>
            </a>
        </div>

        {{-- Le Mans Ultimate --}}
        <div class="play-on-item">
            <a href="https://www.lemansultimate.com/" target="_blank"
               class="xcl-game-badge xcl-game-lmu">
                <img src="/images/home/icons/LM Logo.png" class="xcl-badge-icon" alt="LMU">
                <span class="xcl-badge-text">LMU</span>
            </a>
        </div>

        {{-- iRacing --}}
        <div class="play-on-item">
            <a href="https://www.iracing.com/" target="_blank"
               class="xcl-game-badge xcl-game-iracing">
                <img src="/images/home/icons/iR Logo.png" class="xcl-badge-icon" alt="iRacing">
                <span class="xcl-badge-text">iRACING</span>
            </a>
        </div>

        {{-- ACC PC --}}
        <div class="play-on-item">
            <a href="https://www.assettocorsa.net/competizione/" target="_blank" class="xcl-game-badge xcl-game-acrally">
                <img src="/images/home/icons/ACC Logo.png" class="xcl-badge-icon" alt="ACC PC">
                <span class="xcl-badge-text">ACC PC</span>
            </a>
        </div>
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

    {{-- ── Mobile only: platform icons ──────────────────────────────────────────
         Sign In moved to the white navbar's right zone (see navbar.blade.php's
         .xcl-mobile-nav-row) — the dark top bar on mobile is now icons only.
         "MY PROFILE" is likewise in the hamburger menu — see navbar.blade.php. --}}
    <div class="d-flex d-md-none align-items-center w-100">
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