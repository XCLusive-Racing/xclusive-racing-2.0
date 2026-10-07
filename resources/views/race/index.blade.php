@extends('layouts.app')

@section('title', 'XCL Events - ' . config('xcl.name'))

@section('content')
<main class="events-page xcl-page pb-5 px-3" data-events-filter
      data-events-url="{{ route('events.index') }}"
      data-initial-platform="{{ $initialGame ?? '' }}">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- ── Page header ─────────────────────────────────────────────────────── --}}
        <div class="pt-4 mb-5 xcl-events-header">
            <h1 data-events-heading class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">XCL EVENTS</h1>
            <div class="section-divider" style="margin-left:0"></div>
        </div>

        {{-- ── Platform selector (shown when no platform selected) ────────────── --}}
        <div data-platform-selector>
            <div class="events-platform-grid mb-3">
                @foreach([
                    ['acc',     '#7c3aed', 'ACC Console',     'Assetto Corsa Competizione · PS5 & Xbox Series X/S', '/images/home/icons/ACC Logo.png',  false],
                    ['ac',      '#16a34a', 'ACC PC',           'Assetto Corsa Competizione · PC Sim Racing',         '/images/home/icons/ACC Logo.png',  false],
                    ['lmu',     '#db2877', 'Le Mans Ultimate', 'Le Mans Ultimate · Premium PC Sim Racing',           '/images/home/icons/LM Logo.png',   false],
                    ['iracing', '#2563eb', 'iRacing',          'iRacing · World\'s Leading Online Sim Racing',       '/images/home/icons/iR Logo.png',   false],
                ] as [$game, $color, $label, $desc, $logo, $comingSoon])
                @php
                    $count    = $races->where('game', $game)->where('status', 'open')->count();
                    $hasVideo = file_exists(public_path("videos/{$game}.mp4"));
                @endphp

                <div class="events-platform-card"
                     data-platform-card="{{ $comingSoon ? '' : $game }}"
                     data-platform-label="{{ $label }}"
                     data-platform-url="{{ route('events.platform', \App\Models\Race::PLATFORM_SLUGS[$game]) }}"
                     style="{{ $comingSoon ? 'cursor:default;opacity:.75' : 'cursor:pointer' }}">

                    @if($hasVideo)
                    <video muted loop playsinline preload="metadata" class="events-platform-card__video">
                        <source src="/videos/{{ $game }}.mp4" type="video/mp4">
                    </video>
                    @else
                    <div class="events-platform-card__gradient" style="background:linear-gradient(160deg,{{ $color }}55 0%,{{ $color }}cc 100%)"></div>
                    @endif

                    <div class="events-platform-card__top-bar" style="background:{{ $color }}"></div>

                    @if($comingSoon)
                    <div class="events-platform-card__count" style="background:rgba(0,0,0,.55);color:#d1d5db">
                        Coming Soon
                    </div>
                    @else
                    <div class="events-platform-card__count">
                        {{ $count }} open {{ $count === 1 ? 'event' : 'events' }}
                    </div>
                    @endif

                    <div class="events-platform-card__body">
                        <div class="events-platform-card__title">{!! $label !!}</div>
                        @if(!$comingSoon)
                        <div class="events-platform-card__desc">
                            <p>{{ $desc }}</p>
                            <span class="events-platform-card__cta" style="background:{{ $color }}">
                                View Events →
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Full-width bars under the platform cards: Results and Reports --}}
            <div class="events-link-bars mb-5">
                @foreach([
                    [route('results.index'), '#eab308', 'fa-solid fa-flag-checkered', 'Results',  'Race results and standings from every XCL event'],
                    [route('reports.index'), '#dc2626', 'fa-solid fa-gavel',          'Reports',  'Report an incident or follow up on a stewarding decision'],
                ] as [$href, $color, $icon, $label, $desc])
                <a href="{{ $href }}" class="events-link-bar" style="--bar-color:{{ $color }}">
                    <span class="events-link-bar__icon"><i class="{{ $icon }}"></i></span>
                    <span class="events-link-bar__text">
                        <span class="events-link-bar__title">{{ $label }}</span>
                        <span class="events-link-bar__desc">{{ $desc }}</span>
                    </span>
                    <span class="events-link-bar__cta">View {{ $label }} →</span>
                </a>
                @endforeach
            </div>

            @guest
            <x-cta-banner />
            @endguest
        </div>

        {{-- ── Platform selected: event list (hidden initially) ──────────────── --}}
        <div data-events-list style="display:none">

            {{-- Filters: BACK TO PLATFORMS on its own line; below it the Event Filter
                 category buttons (Formats / Specs / Class) on the left and the
                 always-visible Timezone filter right-aligned on the same line — tap a
                 category to open that group's options below the bar.
                 Same behavior on every screen size (phone through full desktop) so
                 there's a single layout to reason about instead of a separate
                 always-expanded desktop version — see events-filter.js and the
                 .xcl-filters rules in app.scss. --}}
            <div class="mb-4 xcl-filters">

                <button data-back-btn class="events-back-btn mb-3">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
                    </svg>
                    BACK TO PLATFORMS
                </button>

                {{-- Featured — the selected game's Popular Today / Weekly Event / Special Event
                     picks (RaceController::featured()), in a framed panel so they read apart
                     from the regular list below. Shown/hidden with the game's event list
                     (data-game-section); its cards have no data-event-card, so the filters
                     below don't hide them. An empty slot is left out. --}}
                @foreach($featured as $game => $picks)
                <div data-game-section="{{ $game }}" class="xcl-featured" style="display:none">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        @foreach([
                            ['popular', 'Popular Today', 'fa-solid fa-fire',          '#f97316'],
                            ['weekly',  'Weekly Event',  'fa-solid fa-calendar-week', '#3b82f6'],
                            ['special', 'Special Event', 'fa-solid fa-star',          '#eab308'],
                        ] as [$slot, $label, $icon, $color])
                        @if($race = $picks[$slot])
                        <div class="col" data-featured-card="{{ $slot }}" style="--featured-color:{{ $color }}">
                            <div class="xcl-featured__label fw-bold text-uppercase">
                                <i class="{{ $icon }} me-1"></i>{{ $label }}
                            </div>
                            @include('race.partials.event-card')
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endforeach

                <div class="xcl-filters__bar mb-2">
                    {{-- Category row — each button reuses one of the platform-card accent
                         colors so the row reads as color instead of the plain dark
                         default button. --}}
                    <div class="xcl-filters__event">
                        <div class="xcl-filters__categories-label fw-bold text-uppercase mb-1">Event Filter</div>
                        <div class="d-flex gap-2 xcl-filters__categories">
                            @foreach([
                                ['event',        'Formats',   '#7c3aed'],
                                ['requirements', 'Specs',     '#f97316'],
                                ['class',        'Class',     '#16a34a'],
                            ] as [$category, $label, $color])
                            <button data-filter-category="{{ $category }}" data-color="{{ $color }}"
                                    class="xcl-filter-btn fw-bold text-uppercase"
                                    style="border-color:{{ $color }}66;color:{{ $color }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Timezone filter — which region's 14:00-23:59 local slot to
                         show (Race::eveningRegions()); always visible, All Times by default. --}}
                    <div class="xcl-filters__timezone">
                        {{-- Label + which timezone the event times below are in (local-time.js). Region
                             names are shortened to AUS / EU / US on small phones. --}}
                        <div class="d-flex justify-content-start justify-content-md-end align-items-baseline gap-2 mb-1">
                            <span class="xcl-filters__categories-label fw-bold text-uppercase">Timezone</span>
                            <span class="xcl-filters__tz-hint">
                                <span class="d-none d-sm-inline">Times in</span> <span data-viewer-tz>UK time</span>
                                @auth · <a href="{{ route('profile.edit') }}">change</a>@endauth
                            </span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap justify-content-start justify-content-md-end">
                            <button data-region-filter="all"
                                    class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase xcl-filter-btn--active">All Times</button>
                            <button data-region-filter="australia"
                                    class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase"><span class="d-none d-sm-inline">Australia</span><span class="d-sm-none">AUS</span></button>
                            <button data-region-filter="europe"
                                    class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase"><span class="d-none d-sm-inline">Europe</span><span class="d-sm-none">EU</span></button>
                            <button data-region-filter="us"
                                    class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase"><span class="d-none d-sm-inline">America</span><span class="d-sm-none">US</span></button>
                        </div>
                    </div>
                </div>

                <div class="xcl-filters__groups">
                    {{-- Event type filter — a fixed, short-to-long list of real event formats
                         (not every EventTag row that happens to exist), each colored the same
                         as its format's own image so it reads at a glance. --}}
                    <div class="d-flex gap-2 flex-wrap" data-filter-group="event">
                        <button data-event-filter="all"
                                class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase xcl-filter-btn--active">All</button>
                        @foreach([
                            ['supersprint',  'Super',         '#dc2626'],
                            ['sprint',       'Sprint',        '#f97316'],
                            ['daily',        'Daily',         '#eab308'],
                            ['intermediate', 'Intermediate',  '#16a34a'],
                            ['fullrace',     'Full',          '#0d9488'],
                            ['multiclass',   'Multiclass',    '#0ea5e9'],
                            ['longrace',     'Long',          '#4338ca'],
                            ['mini-enduro',  'Mini Enduro',   '#7c3aed'],
                            ['endurance',    'Endurance',     '#9d174d'],
                        ] as [$slug, $label, $color])
                        <button data-event-filter="{{ $slug }}" data-color="{{ $color }}"
                                class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase"
                                style="border-color:{{ $color }}66;color:{{ $color }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    {{-- Requirements filter — each button colored like the tier/status it
                         represents (Open = green, SR+ = orange, Rookie = the site's rookie
                         red, Bronze Only = bronze, Bronze+ = silver — same tier colors as
                         Race::xclTierInfo()), so it reads at a glance like the event-type row. --}}
                    <div class="d-flex gap-2 flex-wrap" data-filter-group="requirements">
                        <button data-requirement-filter="all"
                                class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase xcl-filter-btn--active">All Requirements</button>
                        @foreach([
                            ['open',        'Open',        '#22c55e'],
                            ['sr',          'SR+',         '#f97316'],
                            ['rookie-only', 'Rookie Only', '#ef4444'],
                            ['bronze-only', 'Bronze Only', '#cd7f32'],
                            ['bronze-plus', 'Bronze+',     '#9ca3af'],
                        ] as [$slug, $label, $color])
                        <button data-requirement-filter="{{ $slug }}" data-color="{{ $color }}"
                                class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase"
                                style="border-color:{{ $color }}66;color:{{ $color }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    {{-- Car class filter — colored the same as each class's badge on the
                         event card itself (Race::carClassStyle()), so it reads at a
                         glance. Always shown solid (not just a tinted border like the
                         other rows) — TCX's white badge has no hue, so a translucent
                         tint reads the same as this row's own default white button
                         text and effectively disappears. --}}
                    <div class="d-flex gap-2 flex-wrap" data-filter-group="class">
                        <button data-class-filter="all"
                                class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase xcl-filter-btn--active">All Classes</button>
                        @foreach([
                            ['GT3', '#DC2626', '#FFFFFF', '#DC2626'],
                            ['GT4', '#2563EB', '#FFFFFF', '#2563EB'],
                            ['GT2', '#16A34A', '#FFFFFF', '#16A34A'],
                            ['GTC', '#F97316', '#FFFFFF', '#F97316'],
                            // TCX's white fill has no hue of its own to define an edge
                            // against a dark page background, unlike the others (whose
                            // border just matches their fill) — a neutral gray outline
                            // instead so the chip still reads as its own bounded shape.
                            ['TCX', '#FFFFFF', '#0D0D0D', '#9CA3AF'],
                        ] as [$class, $bg, $text, $border])
                        <button data-class-filter="{{ $class }}" data-bg="{{ $bg }}" data-text="{{ $text }}" data-border="{{ $border }}"
                                class="xcl-filter-btn xcl-filter-btn--sm fw-bold text-uppercase"
                                style="background:{{ $bg }};color:{{ $text }};border-color:{{ $border }};opacity:.65">{{ $class }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            @foreach(['acc', 'ac', 'lmu', 'iracing'] as $game)
            @php $gameRaces = $races->where('game', $game); @endphp
            <div data-game-section="{{ $game }}" style="display:none">

                @if($gameRaces->isEmpty())
                <div class="events-empty">
                    <svg width="48" height="48" fill="none" stroke="rgba(168,85,247,.4)" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <h3 class="fw-black text-uppercase fst-italic mb-2">NO UPCOMING EVENTS</h3>
                    <p>Check back soon for new events!</p>
                </div>
                @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                    @foreach($gameRaces as $race)
                    <div class="col"
                         data-event-card
                         data-tag="{{ $race->event_tag ?? 'daily' }}"
                         data-date="{{ $race->scheduled_at->toIso8601String() }}"
                         data-regions="{{ implode(',', $race->eveningRegions()) }}"
                         data-class="{{ strtoupper(implode(',', $race->displayCarClasses())) }}"
                         data-sr="{{ $race->sr_requirement ? '1' : '0' }}"
                         data-min-rating="{{ $race->min_rating ?? '' }}"
                         data-max-rating="{{ $race->max_rating ?? '' }}">
                        @include('race.partials.event-card')
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>

    </div>
</main>
@endsection
