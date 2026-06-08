<section id="teams" class="meet-team-section py-5" x-data="{ filter: 'all' }">

    <div aria-hidden="true" class="meet-team-pattern"></div>

    <div class="container-xl position-relative" style="z-index:1;">

        {{-- ── Header ────────────────────────────────────────────────────────────── --}}
        <div class="text-center mb-5">
            <p class="mt-eyebrow">EST. XCL · XBOX COMMUNITY LEAGUE</p>
            <h2 class="mt-heading fw-black fst-italic text-uppercase mb-0">
                MEET OUR TEAM
            </h2>
            <hr class="mt-divider">
        </div>

        {{-- ── Filter Tabs ──────────────────────────────────────────────────────── --}}
        <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
            @foreach([
                ['all',     'ALL'],
                ['lmu',     'LMU'],
                ['acc',     'ACC'],
                ['iracing', 'IRACING'],
                ['staff',   'STAFF'],
            ] as [$val, $label])
            <button
                class="mt-filter-btn"
                :class="{ 'mt-filter-btn--active': filter === '{{ $val }}' }"
                x-on:click="filter = '{{ $val }}'">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ── Driver Cards Grid ────────────────────────────────────────────────── --}}
        <div class="row row-cols-2 row-cols-md-5 g-3 mb-4">

            {{-- 1. W. Gigé — LMU / PC / France --}}
            <div class="col" x-show="filter === 'all' || filter === 'lmu'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/W.Gige.png" alt="W. Gigé"
                             style="width:100%;height:100%;object-fit:cover;object-position:top;">
                        <span class="mt-badge mt-badge--game">LMU</span>
                        <span class="mt-badge mt-badge--platform mt-badge--pc">PC</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">W. Gigé</span>
                            <img src="/images/flags/flag-france.png" class="mt-driver-flag" alt="France">
                        </div>
                        <div class="mt-driver-role mt-driver-role--pro">Pro Driver</div>
                    </div>
                </div>
            </div>

            {{-- 2. A. Lucky — LMU / PC / Italy --}}
            <div class="col" x-show="filter === 'all' || filter === 'lmu'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/A.Lucky.png" alt="A. Lucky"
                             style="width:100%;height:100%;object-fit:cover;object-position:top;">
                        <span class="mt-badge mt-badge--game">LMU</span>
                        <span class="mt-badge mt-badge--platform mt-badge--pc">PC</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">A. Lucky</span>
                            <img src="/images/flags/flag-italy.png" class="mt-driver-flag" alt="Italy">
                        </div>
                        <div class="mt-driver-role mt-driver-role--pro">Pro Driver</div>
                    </div>
                </div>
            </div>

            {{-- 3. J. Farish — ACC / Xbox / GB --}}
            <div class="col" x-show="filter === 'all' || filter === 'acc'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/JamesFarish.png" alt="J. Farish"
                             style="width:100%;height:100%;object-fit:cover;object-position:top;">
                        <span class="mt-badge mt-badge--game">ACC</span>
                        <span class="mt-badge mt-badge--platform mt-badge--xbox">Xbox</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">J. Farish</span>
                            <img src="/images/flags/flag-united%20kingdom.png" class="mt-driver-flag" alt="Great Britain">
                        </div>
                        <div class="mt-driver-role mt-driver-role--pro">Pro Driver</div>
                    </div>
                </div>
            </div>

            {{-- 4. P. Soukup — iRacing / PC / US --}}
            <div class="col" x-show="filter === 'all' || filter === 'iracing'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/P.Soukup.png" alt="P. Soukup"
                             style="width:100%;height:100%;object-fit:cover;object-position:top;">
                        <span class="mt-badge mt-badge--game">IRACING</span>
                        <span class="mt-badge mt-badge--platform mt-badge--pc">PC</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">P. Soukup</span>
                            <img src="/images/flags/flag-usa.png" class="mt-driver-flag" alt="United States">
                        </div>
                        <div class="mt-driver-role mt-driver-role--pro">Pro Driver</div>
                    </div>
                </div>
            </div>

            {{-- +29 more — visible only on "all" filter --}}
            <div class="col" x-show="filter === 'all'" x-transition>
                <div class="mt-driver-card mt-driver-card--more">
                    <span class="mt-more-count">+29</span>
                    <span class="mt-more-label">& MORE</span>
                    <a href="/teams" class="mt-more-link">View full roster →</a>
                </div>
            </div>

        </div>

        {{-- ── CTA Buttons ──────────────────────────────────────────────────────── --}}
        <div class="d-flex justify-content-center gap-3 mb-4 flex-wrap">
            <a href="/teams"    class="mt-cta-btn">FULL ROSTER</a>
            <a href="/register" class="mt-cta-btn">JOIN THE TEAM</a>
        </div>

        {{-- ── Role Legend ──────────────────────────────────────────────────────── --}}
        <div class="d-flex justify-content-center flex-wrap gap-4">
            <span class="mt-legend-item">
                <span class="mt-legend-dot" style="background:#7c3aed;"></span>RACE DRIVERS
            </span>
            <span class="mt-legend-item">
                <span class="mt-legend-dot" style="background:#ec4899;"></span>EVENT MANAGERS
            </span>
            <span class="mt-legend-item">
                <span class="mt-legend-dot" style="background:#3b82f6;"></span>STEWARDS
            </span>
            <span class="mt-legend-item">
                <span class="mt-legend-dot" style="background:#eab308;"></span>XCL STAFF
            </span>
        </div>

    </div>
</section>
