<section id="teams" class="meet-team-section py-5" x-data="{ filter: 'all' }">

    <div aria-hidden="true" class="meet-team-pattern"></div>

    <div class="container-xl position-relative" style="z-index:1;">

        {{-- ── Header ────────────────────────────────────────────────────────────── --}}
        <div class="text-center mb-5">
            <p class="mt-eyebrow">EST. 2023 · XCL</p>
            <h2 class="mt-heading fw-black fst-italic text-uppercase mb-0">
                MEET OUR TEAM
            </h2>
            <hr class="mt-divider">
        </div>

        {{-- ── Filter Tabs ──────────────────────────────────────────────────────── --}}
        <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
            @foreach([
                ['all',     'ALL'],
                ['pro',     'PRO'],
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
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 mb-4">

            {{-- 1. Wilson Gigé — LMU / PC / France --}}
            <div class="col" x-show="filter === 'all' || filter === 'lmu'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/W.Gige.png" alt="Wilson Gigé"
                             style="width:100%;height:100%;object-fit:cover;object-position:50% 20%;">
                        <span class="mt-badge mt-badge--game">LMU</span>
                        <span class="mt-badge mt-badge--platform mt-badge--pc">PC</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">Wilson Gigé</span>
                            <img src="/images/flags/flag-france.png" class="mt-driver-flag" alt="France">
                        </div>
                        <div class="mt-driver-role mt-driver-role--esports">Esports Driver</div>
                    </div>
                </div>
            </div>

            {{-- 3. Mats van Rooijen — Hybrid / Racing Driver / Netherlands --}}
            <div class="col" x-show="filter === 'all' || filter === 'pro'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/M.vanRooijen.png" alt="Mats van Rooijen"
                             style="width:100%;height:100%;object-fit:cover;object-position:50% 20%;">
                        <span class="mt-badge mt-badge--game">PRO</span>
                        <span class="mt-badge mt-badge--platform mt-badge--hybrid">Hybrid</span>
                        <div class="mt-driver-socials">
                            <a href="https://matsvrooijen.vercel.app/" class="mt-social-link" title="Website" target="_blank" rel="noopener"><i class="fa-solid fa-globe"></i></a>
                            <a href="https://www.instagram.com/matsvanrooijen_official/" class="mt-social-link" title="Instagram" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a>
                            <a href="https://www.linkedin.com/in/mats-van-rooijen-540354314/" class="mt-social-link" title="LinkedIn" target="_blank" rel="noopener"><i class="fa-brands fa-linkedin"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">Mats van Rooijen</span>
                            <img src="/images/flags/flag-netherlands.png" class="mt-driver-flag" alt="Netherlands">
                        </div>
                        <div class="mt-driver-role mt-driver-role--racing">Professional Driver</div>
                    </div>
                </div>
            </div>

            {{-- 4. Dirk Schouten — PRO / Hybrid / Professional Driver --}}
            <div class="col" x-show="filter === 'all' || filter === 'pro'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/D.Schouten.png" alt="Dirk Schouten"
                             style="width:100%;height:100%;object-fit:cover;object-position:50% 20%;">
                        <span class="mt-badge mt-badge--game">PRO</span>
                        <span class="mt-badge mt-badge--platform mt-badge--hybrid">Hybrid</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">Dirk Schouten</span>
                        </div>
                        <div class="mt-driver-role mt-driver-role--racing">Professional Driver</div>
                    </div>
                </div>
            </div>

            {{-- 5. Parker Soukup — iRacing / PC / US --}}
            <div class="col" x-show="filter === 'all' || filter === 'iracing'" x-transition>
                <div class="mt-driver-card">
                    <div class="mt-driver-portrait">
                        <img src="/images/drivers/P.Soukup.png" alt="Parker Soukup"
                             style="width:100%;height:100%;object-fit:cover;object-position:50% 20%;">
                        <span class="mt-badge mt-badge--game">IRACING</span>
                        <span class="mt-badge mt-badge--platform mt-badge--pc">PC</span>
                        <div class="mt-driver-socials">
                            <a href="#" class="mt-social-link" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" class="mt-social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="mt-driver-info">
                        <div class="mt-driver-name-row">
                            <span class="mt-driver-name">Parker Soukup</span>
                            <img src="/images/flags/flag-usa.png" class="mt-driver-flag" alt="United States">
                        </div>
                        <div class="mt-driver-role mt-driver-role--esports">Esports Driver</div>
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
                <span class="mt-legend-dot" style="background:rgba(192,132,252,0.85);"></span>ESPORTS DRIVERS
            </span>
            <span class="mt-legend-item">
                <span class="mt-legend-dot" style="background:#d4ee6a;"></span>PROFESSIONAL DRIVERS
            </span>
            <span class="mt-legend-item">
                <span class="mt-legend-dot" style="background:#3b82f6;"></span>STAFF
            </span>
        </div>

    </div>
</section>
