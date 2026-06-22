@extends('layouts.app')

@section('title', 'XCL Events - ' . config('xcl.name'))

@section('content')
<main class="events-page xcl-page pb-5 px-3" x-data="eventsFilter()">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- ── Page header ─────────────────────────────────────────────────────── --}}
        <div class="pt-4 mb-5">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">XCL EVENTS</h1>
            <div class="section-divider" style="margin-left:0"></div>
        </div>

        {{-- ── Platform selector ───────────────────────────────────────────────── --}}
        <div x-show="platform === null">
            <div class="events-platform-grid mb-5">
                @foreach([
                    ['acc',     '#7c3aed', 'ACC Console',     'Assetto Corsa Competizione · PS5 & Xbox Series X/S', '/images/home/icons/ACC Logo.png',  false],
                    ['lmu',     '#db2877', 'Le Mans Ultimate', 'Le Mans Ultimate · Premium PC Sim Racing',           '/images/home/icons/LM Logo.png',   false],
                    ['iracing', '#2563eb', 'iRacing',          'iRacing · World\'s Leading Online Sim Racing',       '/images/home/icons/iR Logo.png',   false],
                    ['ac',      '#16a34a', 'AC Rally',         'Assetto Corsa Rally · PC Sim Racing',                '/images/home/icons/AC R Logo.png', true],
                ] as [$game, $color, $label, $desc, $logo, $comingSoon])
                @php
                    $count    = $races->where('game', $game)->where('status', 'open')->count();
                    $hasVideo = file_exists(public_path("videos/{$game}.mp4"));
                @endphp

                <div class="events-platform-card"
                     x-data="{ on: false }"
                     @mouseenter="on = true;  $refs.vid?.play().catch(()=>{})"
                     @mouseleave="on = false; $refs.vid?.pause()"
                     @if(!$comingSoon) @click="selectPlatform('{{ $game }}')" @endif
                     :class="on ? 'events-platform-card--active' : ''"
                     style="{{ $comingSoon ? 'cursor:default;opacity:.75' : '' }}">

                    @if($hasVideo)
                    <video x-ref="vid" muted loop playsinline preload="metadata" class="events-platform-card__video">
                        <source src="/videos/{{ $game }}.mp4" type="video/mp4">
                    </video>
                    @else
                    <div class="events-platform-card__gradient" style="background:linear-gradient(160deg,{{ $color }}55 0%,{{ $color }}cc 100%)"></div>
                    @endif

                    <div class="events-platform-card__top-bar" style="background:{{ $color }}"></div>

                    {{-- Count badge / coming soon top-right --}}
                    @if($comingSoon)
                    <div class="events-platform-card__count" style="background:rgba(0,0,0,.55);color:#d1d5db">
                        Coming Soon
                    </div>
                    @else
                    <div class="events-platform-card__count">
                        {{ $count }} open {{ $count === 1 ? 'event' : 'events' }}
                    </div>
                    @endif

                    {{-- Bottom info --}}
                    <div class="events-platform-card__body">
                        <div class="events-platform-card__title">{!! $label !!}</div>
                        @if(!$comingSoon)
                        <div class="events-platform-card__desc" :class="on ? 'events-platform-card__desc--visible' : ''">
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

            @guest
            <x-cta-banner />
            @endguest
        </div>

        {{-- ── Platform selected: event list ───────────────────────────────────── --}}
        <div x-show="platform !== null" x-cloak>

            <button @click="platform = null" class="events-back-btn mb-4">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                BACK TO PLATFORMS
            </button>

            {{-- Event type filter --}}
            <div class="d-flex gap-2 flex-wrap mb-4">
                <button @click="eventFilter = 'all'"
                        :class="eventFilter === 'all' ? 'xcl-filter-btn--active' : ''"
                        class="xcl-filter-btn fw-bold text-uppercase">All</button>
                @foreach($eventTags as $tag)
                <button @click="eventFilter = '{{ $tag->slug }}'"
                        :class="eventFilter === '{{ $tag->slug }}' ? 'xcl-filter-btn--active' : ''"
                        class="xcl-filter-btn fw-bold text-uppercase">{{ $tag->name }}</button>
                @endforeach
            </div>

            @foreach(['acc', 'lmu', 'iracing', 'ac'] as $game)
            @php $gameRaces = $races->where('game', $game); @endphp
            <div x-show="platform === '{{ $game }}'">

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
                    @php
                        $titleLower = strtolower($race->title ?? '');
                        if ($race->is_championship) {
                            $badge = 'SR5 GRID';
                        } elseif (str_contains($titleLower, 'multiclass') || str_contains($titleLower, 'endurance')) {
                            $badge = 'MULTICLASS';
                        } else {
                            $badge = 'DAILY SPRINT';
                        }
                        $gameShort = match($race->game) {
                            'acc'     => 'ACC',
                            'lmu'     => 'LMU',
                            'iracing' => 'iRACING',
                            'ac'      => 'AC RALLY',
                            default   => strtoupper($race->game),
                        };
                    @endphp
                    <div class="col" x-show="matchesEventFilter('{{ $race->event_tag ?? 'daily' }}', '{{ $race->scheduled_at->toIso8601String() }}')">
                        <div class="xcl-ec2">
                            <div class="xcl-ec2__img-wrap">
                                @if($race->image)
                                    <img src="{{ $race->image_url }}" alt="{{ $race->title }}" loading="lazy" class="xcl-ec2__img">
                                @else
                                    <div class="xcl-ec2__img-placeholder"></div>
                                @endif
                                <div class="xcl-ec2__badge-wrap">
                                    @if($race->icon)
                                    <div class="xcl-ec2__icon-badge">
                                        <img src="{{ $race->icon_url }}" alt="{{ $race->title }}" class="xcl-ec2__icon-badge-img">
                                    </div>
                                    @else
                                    <div class="xcl-ec2__badge">
                                        <div class="xcl-ec2__badge-main">{{ $badge }}</div>
                                        <div class="xcl-ec2__badge-sub">{{ $gameShort }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="xcl-ec2__body">
                                <div class="xcl-ec2__time">
                                    {{ strtoupper($race->scheduledAtUk()->format('l')) }} /
                                    {{ strtoupper($race->scheduledAtUk()->format('g:i A T')) }}
                                </div>
                                <div class="xcl-ec2__meta">
                                    {{ $race->scheduledAtUk()->format('D, M d') }}
                                    @if($race->track) | {{ $race->track }} @endif
                                </div>
                                <a href="{{ route('events.show', $race) }}" class="xcl-see-event-btn">SEE EVENT</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="text-center mt-5">
                    <button x-show="weeksShown === 1" @click="weeksShown = 2" class="xcl-load-btn">
                        LOAD MORE
                    </button>
                    <button x-show="weeksShown === 2" @click="weeksShown = 1" class="xcl-load-btn">
                        LOAD LESS
                    </button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

    </div>
</main>
@endsection