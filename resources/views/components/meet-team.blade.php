<section id="teams" class="meet-team-section py-5" data-meet-team>

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
            <button class="mt-filter-btn" data-filter-btn data-filter-val="{{ $val }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ── Carousel ─────────────────────────────────────────────────────────── --}}
        <div class="position-relative mb-4" style="padding: 0 28px;">

            {{-- Left arrow (hidden initially: current=0) --}}
            <button class="mt-carousel-arrow mt-carousel-arrow--left"
                    data-carousel-prev
                    style="display:none"
                    aria-label="Previous">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Track wrapper --}}
            <div class="mt-carousel-wrapper" data-carousel-wrapper>
                <div class="mt-carousel-track" data-carousel-track></div>
            </div>

            {{-- Right arrow --}}
            <button class="mt-carousel-arrow mt-carousel-arrow--right"
                    data-carousel-next
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
