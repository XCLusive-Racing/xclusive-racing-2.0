<section id="teams" class="meet-team-section py-5" x-data="driverCarousel()">

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
                @click="setFilter('{{ $val }}')">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ── Carousel ─────────────────────────────────────────────────────────── --}}
        <div class="position-relative mb-4" style="padding: 0 28px;">

            {{-- Left arrow --}}
            <button class="mt-carousel-arrow mt-carousel-arrow--left"
                    x-show="current > 0"
                    x-transition
                    @click="prev()"
                    aria-label="Previous">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Track wrapper --}}
            <div class="mt-carousel-wrapper" x-ref="wrapper">
                <div class="mt-carousel-track"
                     :style="`transform: translateX(-${current * ($refs.wrapper ? $refs.wrapper.offsetWidth / perPage : 0)}px); transition: transform 0.3s ease;`">

                    {{-- Driver cards (rendered from Alpine data) --}}
                    <template x-for="driver in filtered" :key="driver.name">
                        <div class="mt-carousel-item">
                            <div class="mt-driver-card">
                                <div class="mt-driver-portrait" :class="{ 'mt-driver-portrait--blank': !driver.photo }">
                                    <img x-show="driver.photo"
                                         :src="driver.photo || ''"
                                         :alt="driver.name"
                                         style="width:100%;height:100%;object-fit:cover;object-position:50% 40%;">
                                    <span class="mt-badge mt-badge--game" x-text="gameBadgeLabel(driver.cat)"></span>
                                    <span class="mt-badge mt-badge--platform"
                                          :class="platformBadgeClass(driver.platform)"
                                          x-text="driver.platformLabel"></span>
                                    <div class="mt-driver-socials">
                                        <template x-for="social in driver.socials" :key="social.type">
                                            <a :href="social.href"
                                               class="mt-social-link"
                                               :title="social.type"
                                               :target="social.href !== '#' ? '_blank' : null"
                                               :rel="social.href !== '#' ? 'noopener noreferrer' : null">
                                                <i :class="socialIcon(social.type)"></i>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                                <div class="mt-driver-info">
                                    <div class="mt-driver-name-row">
                                        <span class="mt-driver-name" x-text="driver.name"></span>
                                        <template x-if="driver.flag">
                                            <img :src="flagSrc(driver.flag)" class="mt-driver-flag" :alt="driver.flag">
                                        </template>
                                    </div>
                                    <div class="mt-driver-role"
                                         :class="roleClass(driver.role)"
                                         x-text="roleLabel(driver.role)"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- +MORE card — always last --}}
                    <div class="mt-carousel-item">
                        <div class="mt-driver-card mt-driver-card--more">
                            <span class="mt-more-count">+29</span>
                            <span class="mt-more-label">& MORE</span>
                            <a href="/teams" class="mt-more-link">View full roster →</a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Right arrow --}}
            <button class="mt-carousel-arrow mt-carousel-arrow--right"
                    x-show="current < maxCurrent"
                    x-transition
                    @click="next()"
                    aria-label="Next">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

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

<script>
function driverCarousel() {
    return {
        filter: 'all',
        current: 0,

        drivers: [
            // ── PRO ──────────────────────────────────────────────────────
            {
                name: 'Mats van Rooijen',
                cat: 'pro', platform: 'hybrid', platformLabel: 'Hybrid',
                photo: '/images/drivers/M.vanRooijen.png',
                flag: 'netherlands', role: 'racing',
                socials: [
                    { type: 'website',   href: 'https://matsvrooijen.vercel.app/' },
                    { type: 'instagram', href: 'https://www.instagram.com/matsvanrooijen_official/' },
                    { type: 'linkedin',  href: 'https://www.linkedin.com/in/mats-van-rooijen-540354314/' },
                ],
            },
            {
                name: 'Dirk Schouten',
                cat: 'pro', platform: 'hybrid', platformLabel: 'Hybrid',
                photo: '/images/drivers/D.Schouten.png',
                flag: 'netherlands', role: 'racing',
                socials: [{ type: 'twitter', href: '#' }, { type: 'instagram', href: '#' }],
            },

            // ── LMU ──────────────────────────────────────────────────────
            { name: 'Giuseppe Dinoia',   cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'italy',            role: 'esports', socials: [] },
            { name: 'Denis Ebert',       cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
            { name: 'Wilson Gigé',       cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: '/images/drivers/W.Gige.png',   flag: 'france',            role: 'esports', socials: [] },
            { name: 'Luca Gönnheimer',   cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
            { name: 'Kyan Heyninck',     cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'belgium',          role: 'esports', socials: [] },
            { name: 'Alex Lucky',        cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: '/images/drivers/A.Lucky.png',  flag: 'italy',             role: 'esports', socials: [] },
            { name: 'Paul Möller',       cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
            { name: 'Thato Motubatse',   cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'south%20africa',   role: 'esports', socials: [] },
            { name: 'Lukas Oesterreich', cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
            { name: 'Gianluca Walczak',  cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany',          role: 'esports', socials: [] },
            { name: 'Kyle Williams',     cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'south%20africa',   role: 'esports', socials: [] },
            { name: 'Aidan Winchester',  cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'united%20kingdom', role: 'esports', socials: [] },
            { name: 'Jesse Aalbregt',    cat: 'lmu', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'netherlands',      role: 'esports', socials: [] },

            // ── ACC ──────────────────────────────────────────────────────
            { name: 'Nat Benett',          cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'united%20kingdom', role: 'esports', socials: [] },
            { name: 'Joakim Eriksson',     cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
            { name: 'Fabio Faar',          cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },
            { name: 'James Farish',        cat: 'acc', platform: 'console', platformLabel: 'Console', photo: '/images/drivers/J.Farish.png', flag: 'united%20kingdom', role: 'esports', socials: [] },
            { name: 'Will Friedmann',      cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'france',          role: 'esports', socials: [] },
            { name: 'José García',         cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
            { name: 'Sergio Hernández',    cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
            { name: 'Matteo Mastromauro',  cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },
            { name: 'Danny Meeldijk',      cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'netherlands',     role: 'esports', socials: [] },
            { name: 'Elmārs Miķelsons',    cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'latvia',          role: 'esports', socials: [] },
            { name: 'Florian Ochsmann',    cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'germany',         role: 'esports', socials: [] },
            { name: 'Menno Peters',        cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'netherlands',     role: 'esports', socials: [] },
            { name: 'Phil Sourcy',         cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: null,              role: 'esports', socials: [] },
            { name: 'Gianluca Zambione',   cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },
            { name: 'Federico Zamblera',   cat: 'acc', platform: 'console', platformLabel: 'Console', photo: null, flag: 'italy',           role: 'esports', socials: [] },

            // ── iRacing ──────────────────────────────────────────────────
            { name: 'Ethan Amburg',    cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
            { name: 'James Curtin',    cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
            { name: 'CJ Farish',       cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
            { name: 'Mario García',    cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: null,      role: 'esports', socials: [] },
            { name: 'Jake Goldman',    cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'usa',     role: 'esports', socials: [] },
            { name: 'Michael Martinz', cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'austria', role: 'esports', socials: [] },
            { name: 'Parker Soukup',   cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: '/images/drivers/P.Soukup.png', flag: 'usa', role: 'esports', socials: [] },
            { name: 'André Damrat',    cat: 'iracing', platform: 'pc', platformLabel: 'PC', photo: null, flag: 'germany', role: 'esports', socials: [] },
        ],

        get filtered() {
            return this.filter === 'all'
                ? this.drivers
                : this.drivers.filter(d => d.cat === this.filter);
        },

        get perPage() {
            return window.innerWidth >= 768 ? 5 : 2;
        },

        get maxCurrent() {
            return Math.max(0, this.filtered.length + 1 - this.perPage); // +1 for MORE card
        },

        setFilter(f) {
            this.filter = f;
            this.current = 0;
        },

        prev() {
            if (this.current > 0) this.current--;
        },

        next() {
            if (this.current < this.maxCurrent) this.current++;
        },

        gameBadgeLabel(cat) {
            return { lmu: 'LMU', acc: 'ACC', iracing: 'IRACING', pro: 'PRO', staff: 'STAFF' }[cat]
                || cat.toUpperCase();
        },

        platformBadgeClass(platform) {
            return { pc: 'mt-badge--pc', hybrid: 'mt-badge--hybrid', xbox: 'mt-badge--xbox', ps5: 'mt-badge--ps5', console: 'mt-badge--console' }[platform] || '';
        },

        roleLabel(role) {
            return { esports: 'Esports Driver', racing: 'Professional Driver', staff: 'Staff' }[role] || role;
        },

        roleClass(role) {
            return { esports: 'mt-driver-role--esports', racing: 'mt-driver-role--racing', staff: 'mt-driver-role--staff' }[role] || '';
        },

        socialIcon(type) {
            return {
                twitter:   'fa-brands fa-x-twitter',
                instagram: 'fa-brands fa-instagram',
                website:   'fa-solid fa-globe',
                linkedin:  'fa-brands fa-linkedin',
                facebook:  'fa-brands fa-facebook',
                twitch:    'fa-brands fa-twitch',
            }[type] || 'fa-solid fa-link';
        },

        flagSrc(flag) {
            return `/images/flags/flag-${flag}.png`;
        },
    };
}
</script>
