@extends('layouts.app')

@section('title', 'Coaching - ' . config('xcl.name'))

@section('content')

{{-- ─── Hero ──────────────────────────────────────────────────────────────── --}}
<section class="coaching-hero">
    <div class="container-xl px-4 position-relative" style="z-index:1;">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="coaching-hero__badge mb-2">Official Coaching Partner of XCLusive Racing</p>
                <h1 class="fw-black text-uppercase fst-italic lh-1 mb-3" style="font-size:clamp(2rem, 4.5vw, 3.5rem)">
                    COACHING
                </h1>
                <p class="mb-0" style="font-size:1.15rem;color:rgba(255,255,255,.85)">
                    Elite sim racing coaching — powered by DriveLab
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                {{-- User-provided asset: public/images/partners/drivelab-logo.avif --}}
                <img src="/images/partners/drivelab-logo.avif" alt="DriveLab Coaching" class="coaching-hero__partner-logo"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <span class="fw-black text-uppercase fst-italic" style="display:none;font-size:1.5rem;color:rgba(255,255,255,.5)">
                    DriveLab
                </span>
            </div>
        </div>
    </div>
</section>

{{-- ─── Meet The Coaches ──────────────────────────────────────────────────── --}}
<section id="coaches" class="py-5 px-3 position-relative" style="background:white">
    <div class="about-section__topo" style="background-image:url('/topo.png');"></div>

    <div class="container-xl position-relative" style="z-index:1">

        <div class="text-center mb-5 mt-header">
            <h2 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">MEET THE COACHES</h2>
            <div class="section-divider mb-3"></div>
            <p class="mb-0 mx-auto mb-4" style="color:#6b7280;font-size:.95rem;max-width:560px">
                All coaches are professional sim racers competing at the highest level.
            </p>

            {{-- Game + platform filter --}}
            <div class="coaching-filter-bar coaching-filter-bar--light" data-coaching-filter>
                <div class="coaching-filter-group">
                    <span class="coaching-filter-label">GAME</span>
                    <button type="button" class="mt-filter-btn mt-filter-btn--active" data-game-filter="all">ALL</button>
                    <button type="button" class="mt-filter-btn" data-game-filter="lmu">LMU</button>
                    <button type="button" class="mt-filter-btn" data-game-filter="acc">ACC</button>
                    <button type="button" class="mt-filter-btn" data-game-filter="iracing">IRACING</button>
                </div>
                <div class="coaching-filter-group">
                    <span class="coaching-filter-label">PLATFORM</span>
                    <button type="button" class="mt-filter-btn mt-filter-btn--active" data-platform-filter="pc">PC</button>
                    <button type="button" class="mt-filter-btn" data-platform-filter="ps5">PS5</button>
                    <button type="button" class="mt-filter-btn" data-platform-filter="xbox">XBOX</button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @foreach($coaches as $coach)
            <div class="col-md-4" data-coach-games="{{ implode(' ', $coach['games']) }}" data-coach-platforms="{{ implode(' ', $coach['platforms']) }}">
                <div class="coach-card h-100">
                    <div class="coach-card__photo-wrap">
                        {{-- User-provided asset: {{ $coach['photo'] }} --}}
                        <img src="{{ $coach['photo'] }}" alt="{{ $coach['name'] }}" class="coach-card__photo"
                             style="object-position: {{ $coach['photo_position'] ?? 'top center' }}; transform: scale({{ $coach['photo_zoom'] ?? 1 }});"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="coach-card__photo-fallback" style="display:none">
                            {{ strtoupper(substr($coach['name'], 0, 1)) }}
                        </div>
                    </div>
                    <div class="coach-card__body">
                        <h3 class="coach-card__name">{{ $coach['name'] }}</h3>
                        <ul class="coach-card__achievements">
                            @foreach($coach['achievements'] as $achievement)
                            <li>{{ $achievement }}</li>
                            @endforeach
                        </ul>
                        <div class="coach-card__games">
                            @foreach($coach['games'] as $game)
                            <x-game-badge :game="$game" />
                            @endforeach
                        </div>
                        <a href="#packages" class="xcl-coaching-btn">Book a Session</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- ─── Trusted Setup Creators ────────────────────────────────────────────── --}}
<section class="coaching-setups-section py-5 px-3 position-relative" style="background:white">
    <div class="container-xl position-relative" style="z-index:1">

        <div class="text-center mb-4">
            <h2 class="fw-black text-uppercase fst-italic about-section__heading mb-0" style="font-size:clamp(1.4rem, 2.4vw, 2rem)">
                TRUSTED SETUP CREATORS
            </h2>
        </div>

        <div class="row g-4 justify-content-center">
            @foreach($setupCreators as $setup)
            <div class="col-md-5">
                <div class="setup-card h-100">
                    <div class="setup-card__title">{{ $setup['name'] }}</div>
                    <div class="setup-card__by">by {{ $setup['by'] }}</div>
                    <p class="setup-card__desc">{{ $setup['desc'] }}</p>
                    <a href="{{ $setup['href'] }}" class="setup-card__link">VIEW SETUPS →</a>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- ─── Book A Session / Packages ─────────────────────────────────────────── --}}
<section id="packages" class="meet-team-section py-5">
    <div class="container-xl position-relative" style="z-index:1;">

        <div class="text-center mb-5 mt-header">
            <h2 class="mt-heading fw-black fst-italic text-uppercase mb-0">BOOK A SESSION</h2>
            <hr class="mt-divider">
            <p class="mb-0 mx-auto" style="color:rgba(255,255,255,.7);font-size:.95rem;max-width:480px">
                Choose your coach and session type.
            </p>
        </div>

        @foreach($packageTiers as $tier)
        <div class="mb-3">
            <p class="mt-eyebrow text-center mb-3">{{ $tier['label'] }} · {{ $tier['price'] }} · {{ $tier['duration'] }}</p>
        </div>
        <div class="row g-4 {{ !$loop->last ? 'mb-5' : '' }}">
            @foreach($coaches as $coach)
            <div class="col-md-4" data-coach-games="{{ implode(' ', $coach['games']) }}" data-coach-platforms="{{ implode(' ', $coach['platforms']) }}">
                <div class="package-card h-100">
                    {{-- User-provided asset: {{ $coach['photo'] }} --}}
                    <img src="{{ $coach['photo'] }}" alt="{{ $coach['name'] }}" class="package-card__avatar"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="package-card__avatar-fallback" style="display:none">
                        {{ strtoupper(substr($coach['name'], 0, 1)) }}
                    </div>

                    <div class="package-card__badge package-card__badge--{{ $tier['key'] }}">{{ strtoupper($tier['key']) }}</div>

                    <div class="package-card__coach">{{ $coach['name'] }}</div>
                    <div class="package-card__price">{{ $coach['pricing'][$tier['key']] ?? $tier['price'] }}</div>
                    <div class="package-card__duration">{{ $tier['duration'] }} session</div>
                    <p class="package-card__desc">{{ $tier['description'] }}</p>
                    <div class="package-card__games">
                        @foreach($coach['games'] as $game)
                        <x-game-badge :game="$game" />
                        @endforeach
                    </div>
                    <a href="#" class="xcl-coaching-btn">BOOK NOW</a>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach

    </div>
</section>

{{-- ─── CTA ────────────────────────────────────────────────────────────────── --}}
<section class="py-5 px-3" style="background:white">
    <div class="container-xl">
        <x-cta-banner
            heading="READY TO LEVEL UP?"
            subtext="Book your session with one of our DriveLab coaches today."
            button="BOOK NOW →"
            href="#packages"
        />
    </div>
</section>

@endsection
