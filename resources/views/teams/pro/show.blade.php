@extends('layouts.app')

@section('title', $driver['name'] . ' — Professional Driver — ' . config('xcl.name'))

@section('content')

@php
$socialIcons = [
    'instagram' => 'fa-brands fa-instagram',
    'tiktok'    => 'fa-brands fa-tiktok',
    'youtube'   => 'fa-brands fa-youtube',
    'linkedin'  => 'fa-brands fa-linkedin',
    'facebook'  => 'fa-brands fa-facebook',
    'twitter'   => 'fa-brands fa-x-twitter',
    'twitch'    => 'fa-brands fa-twitch',
    'website'   => 'fa-solid fa-globe',
];

$posStyle = fn(string $pos): string => match(true) {
    (int) ltrim($pos, 'P') === 1  => 'color:#fbbf24',
    (int) ltrim($pos, 'P') === 2  => 'color:#9ca3af',
    (int) ltrim($pos, 'P') === 3  => 'color:#cd7f32',
    (int) ltrim($pos, 'P') <= 10  => 'color:#d4ee6a',
    default                        => 'color:#6b7280',
};

// Default to the most recent year that has actual results; fall back to the highest year key
$latestYear = (int) collect($driver['results'])
    ->filter(fn($r) => !empty($r))
    ->keys()
    ->max() ?? max(array_keys($driver['results']));

$upcomingRaces = \App\Models\TeamEvent::upcoming()
    ->forSubject($driver['slug'])
    ->limit(3)
    ->get();
@endphp

<main class="pro-driver-page">

    {{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
    <section class="pro-driver-hero">

        {{-- Hero image (landscape from media library) --}}
        @if($driver['hero'])
        <img src="{{ $driver['hero'] }}" alt="{{ $driver['name'] }}" class="pro-driver-hero__bg">
        @else
        <div class="pro-driver-hero__portrait-wrap">
            <img src="{{ $driver['portrait'] }}" alt="{{ $driver['name'] }}" class="pro-driver-hero__portrait">
        </div>
        @endif

        <div class="pro-driver-hero__overlay"></div>

        <div class="pro-driver-hero__content container-xl px-4">

            {{-- Back --}}
            <a href="{{ route('teams.pro.index') }}" class="pro-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                All Drivers
            </a>

            <div class="pro-driver-hero__info">
                {{-- Flag + nationality --}}
                <div class="pro-driver-hero__meta">
                    <img src="/images/flags/flag-{{ $driver['flag'] }}.png"
                         alt="{{ $driver['nationality'] }}"
                         class="pro-driver-hero__flag">
                    <span class="pro-driver-hero__nationality">{{ strtoupper($driver['nationality']) }}</span>
                    <span class="pro-driver-hero__dot">·</span>
                    <span class="pro-driver-hero__nationality">PROFESSIONAL DRIVER</span>
                </div>

                {{-- Name --}}
                <h1 class="pro-driver-hero__name">{{ strtoupper($driver['name']) }}</h1>

                {{-- Socials --}}
                @if(!empty($driver['socials']))
                <div class="pro-driver-hero__socials">
                    @foreach($driver['socials'] as $s)
                    <a href="{{ $s['href'] }}" target="_blank" rel="noopener" class="pro-driver-social-btn">
                        <i class="{{ $socialIcons[$s['type']] ?? 'fa-solid fa-link' }}"></i>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ── Body ─────────────────────────────────────────────────────────────── --}}
    <div class="pro-driver-body container-xl px-4">

        {{-- Upcoming Races --}}
        @if($upcomingRaces->isNotEmpty() || $driver['profile_image'])
        <section class="pro-upcoming-races">
            <div style="display:flex;gap:1.5rem;align-items:flex-start">

                {{-- Profile image box --}}
                @if($driver['profile_image'])
                <div style="flex:0 0 280px;border-radius:12px;overflow:hidden;border:1px solid rgba(212,238,106,0.15);box-shadow:0 4px 24px rgba(0,0,0,0.4);align-self:stretch">
                    <img src="{{ $driver['profile_image'] }}"
                         alt="{{ $driver['name'] }}"
                         style="width:100%;height:100%;object-fit:cover;display:block">
                </div>
                @endif

                {{-- Events column --}}
                <div style="flex:1;min-width:0">
                    <div class="pro-section-label">UPCOMING RACES</div>
                    @if($upcomingRaces->isNotEmpty())
                    <div class="pro-upcoming-list">
                        @foreach($upcomingRaces as $race)
                        <div class="pro-upcoming-card"
                             data-countdown="{{ $race->starts_at->toIso8601String() }}"
                             @if($race->image_url) style="background-image:url('{{ $race->image_url }}');background-size:cover;background-position:center" @endif>
                            @if($race->image_url)<div class="pro-upcoming-card__img-overlay"></div>@endif
                            <div class="pro-upcoming-card__info">
                                <div class="pro-upcoming-card__title">{{ $race->title }}</div>
                                @if($race->subtitle)
                                <div class="pro-upcoming-card__sub">{{ $race->subtitle }}</div>
                                @endif
                                <div class="pro-upcoming-card__date">
                                    {{ $race->starts_at->timezone('Europe/London')->format('d M Y · H:i T') }}
                                </div>
                            </div>
                            <div class="pro-upcoming-card__right">
                                <div class="pro-upcoming-countdown">
                                    <span data-cd-d>00</span><span class="pro-upcoming-countdown__sep">d</span>
                                    <span data-cd-h>00</span><span class="pro-upcoming-countdown__sep">h</span>
                                    <span data-cd-m>00</span><span class="pro-upcoming-countdown__sep">m</span>
                                </div>
                                @if($race->watch_url)
                                <a href="{{ $race->watch_url }}" target="_blank" rel="noopener"
                                   class="pro-upcoming-watch">
                                    ▶ WATCH LIVE
                                </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p style="color:#6b7280;font-size:.85rem;margin-top:.75rem">No upcoming races scheduled.</p>
                    @endif
                </div>

            </div>
        </section>
        @endif

        {{-- Bio + Social Reach --}}
        <section class="pro-driver-about">

            <div class="pro-driver-bio">
                <div class="pro-section-label">ABOUT</div>
                <p class="pro-driver-bio__text">{{ $driver['bio'] }}</p>
            </div>

            @if(!empty($driver['followers']))
            <div class="pro-reach-card">
                <div class="pro-section-label">SOCIAL REACH</div>

                @if(!empty($driver['followers']['headline']))
                <p class="pro-reach-card__quote">"{{ $driver['followers']['headline'] }}"</p>
                @endif

                <div class="pro-reach-stats">
                    @foreach($driver['followers']['stats'] as $stat)
                    <div class="pro-reach-stat">
                        <div class="pro-reach-stat__icon-wrap">
                            <i class="{{ $socialIcons[$stat['type']] ?? 'fa-solid fa-link' }}"></i>
                        </div>
                        <div class="pro-reach-stat__number">{{ $stat['count'] }}</div>
                        <div class="pro-reach-stat__label">{{ $stat['label'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </section>

        {{-- Results with year slider --}}
        <section class="pro-driver-results"
                 data-tabs data-default-tab="{{ $latestYear }}">

            <div class="pro-driver-results__header">
                <div class="pro-section-label">RACE RESULTS</div>

                {{-- Year tabs --}}
                <div class="pro-year-tabs">
                    @foreach(array_keys($driver['results']) as $y)
                    <button class="pro-year-tab"
                            data-tab-btn="{{ $y }}"
                            data-tab-active-class="pro-year-tab--active">
                        {{ $y }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Per-year content --}}
            @foreach($driver['results'] as $year => $championships)
            <div data-tab-panel="{{ $year }}" style="display:none">

                @if(empty($championships))
                    <div class="pro-no-results">
                        <svg width="32" height="32" fill="none" stroke="#6b7280" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="mb-0">No results recorded for {{ $year }} yet.</p>
                    </div>
                @else
                    @foreach($championships as $champ)
                    <div class="pro-championship-block">

                        {{-- Championship header --}}
                        <div class="pro-championship-header">
                            <span class="pro-championship-name">{{ $champ['championship'] }}</span>
                            @if(!empty($champ['standing']))
                            <span class="pro-championship-standing">{{ $champ['standing'] }}</span>
                            @endif
                        </div>

                        {{-- Race results table --}}
                        @if(!empty($champ['races']))
                        <div class="pro-race-table">
                            <div class="pro-race-table__head">
                                <span>CIRCUIT</span>
                                @if(isset($champ['races'][0]['class']))
                                <span>CLASS</span>
                                @endif
                                <span>RESULT{{ count($champ['races'][0]['positions'] ?? []) > 1 ? 'S' : '' }}</span>
                            </div>

                            @foreach($champ['races'] as $race)
                            <div class="pro-race-row">
                                <span class="pro-race-track">{{ $race['track'] }}</span>
                                @if(isset($race['class']))
                                <span class="pro-race-class">{{ $race['class'] }}</span>
                                @endif
                                <span class="pro-race-positions">
                                    @foreach($race['positions'] as $pos)
                                    <span class="pro-pos-badge" style="{{ $posStyle($pos) }}">{{ $pos }}</span>
                                    @endforeach
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                    </div>
                    @endforeach
                @endif

            </div>
            @endforeach

        </section>

    </div>

</main>
@endsection
