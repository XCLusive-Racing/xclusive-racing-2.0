@extends('layouts.app')

@section('title', $driver->name . ' — Esports Driver — ' . config('xcl.name'))

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
@endphp

<main class="pro-driver-page">

    {{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
    <section class="pro-driver-hero">

        <div class="pro-driver-hero__portrait-wrap">
            @if($driver->photo_url)
            <img src="{{ $driver->photo_url }}" alt="{{ $driver->name }}" class="pro-driver-hero__portrait">
            @endif
        </div>

        <div class="pro-driver-hero__overlay"></div>

        <div class="pro-driver-hero__content container-xl px-4">

            <a href="{{ route('teams.esports.index') }}" class="pro-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                All Drivers
            </a>

            <div class="pro-driver-hero__info">
                <div class="pro-driver-hero__meta">
                    @if($driver->flag)
                    <img src="/images/flags/flag-{{ $driver->flag }}.png" alt="" class="pro-driver-hero__flag">
                    @endif
                    <span class="pro-driver-hero__nationality">{{ \App\Models\EsportsDriver::gameLabel($driver->game) }}</span>
                    <span class="pro-driver-hero__dot">·</span>
                    <span class="pro-driver-hero__nationality">ESPORTS DRIVER</span>
                </div>

                <h1 class="pro-driver-hero__name">{{ strtoupper($driver->name) }}</h1>

                @if(!empty($driver->socials))
                <div class="pro-driver-hero__socials">
                    @foreach($driver->socials as $s)
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

        {{-- Upcoming Events --}}
        <section class="pro-upcoming-races">
            <div class="pro-section-label">UPCOMING EVENTS</div>

            @if($upcomingEvents->isNotEmpty())
            <div class="pro-upcoming-list">
                @foreach($upcomingEvents as $event)
                <div class="pro-upcoming-card"
                     data-countdown="{{ $event->starts_at->toIso8601String() }}"
                     @if($event->image_url) style="background-image:url('{{ $event->image_url }}');background-size:cover;background-position:center" @endif>
                    @if($event->image_url)<div class="pro-upcoming-card__img-overlay"></div>@endif
                    <div class="pro-upcoming-card__info">
                        <div class="pro-upcoming-card__title">{{ $event->title }}</div>
                        @if($event->subtitle)
                        <div class="pro-upcoming-card__sub">{{ $event->subtitle }}</div>
                        @endif
                        <div class="pro-upcoming-card__date">
                            {{ $event->starts_at->timezone('Europe/London')->format('d M Y · H:i T') }}
                        </div>
                    </div>
                    <div class="pro-upcoming-card__right">
                        <div class="pro-upcoming-countdown">
                            <span data-cd-d>00</span><span class="pro-upcoming-countdown__sep">d</span>
                            <span data-cd-h>00</span><span class="pro-upcoming-countdown__sep">h</span>
                            <span data-cd-m>00</span><span class="pro-upcoming-countdown__sep">m</span>
                        </div>
                        @if($event->watch_url)
                        <a href="{{ $event->watch_url }}" target="_blank" rel="noopener"
                           class="pro-upcoming-watch">
                            ▶ WATCH LIVE
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p style="color:#6b7280;font-size:.85rem;margin-top:.75rem">No upcoming events scheduled.</p>
            @endif
        </section>

        {{-- Results --}}
        @if($resultsByYear->isNotEmpty())
        <section class="pro-driver-results" data-tabs data-default-tab="{{ $latestResultYear }}">

            <div class="pro-driver-results__header">
                <div class="pro-section-label">RESULTS</div>

                <div class="pro-year-tabs">
                    @foreach($resultsByYear->keys()->sortDesc() as $y)
                    <button class="pro-year-tab" data-tab-btn="{{ $y }}" data-tab-active-class="pro-year-tab--active">
                        {{ $y }}
                    </button>
                    @endforeach
                </div>
            </div>

            @foreach($resultsByYear as $year => $yearResults)
            <div data-tab-panel="{{ $year }}" style="display:none">
                @foreach($yearResults as $result)
                <div class="pro-championship-block">

                    @php
                        $firstRace = $result->races->first();
                        // The track only doubles as the header when there's no separate
                        // title — otherwise it (and the date) show as a subtitle instead,
                        // so the track is never silently dropped from the page.
                        $subtitleParts = $result->title
                            ? array_filter([$firstRace?->track, $firstRace?->race_date?->format('d M Y')])
                            : array_filter([$firstRace?->race_date?->format('d M Y')]);
                    @endphp
                    <div class="pro-championship-header">
                        <span class="pro-championship-name">
                            {{ $result->title ?: ($firstRace?->track ?? 'Event Result') }}
                        </span>
                        @if(!empty($subtitleParts))
                        <span class="pro-championship-standing">
                            {{ implode(' · ', $subtitleParts) }}
                        </span>
                        @endif
                    </div>

                    @foreach($result->races as $race)
                    <div class="pro-race-table">
                        <div class="pro-race-table__head">
                            <span>DRIVER</span>
                            @if($race->car_class)
                            <span>CLASS</span>
                            @endif
                            <span>RESULT</span>
                        </div>

                        @foreach($race->positions as $pos)
                        <div class="pro-race-row">
                            <span class="pro-race-track">{{ $pos->driver->name ?? '—' }}</span>
                            @if($race->car_class)
                            <span class="pro-race-class">{{ $race->car_class }}</span>
                            @endif
                            <span class="pro-race-positions">
                                <span class="pro-pos-badge" style="{{ $posStyle($pos->position) }}">{{ $pos->position }}</span>
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @endforeach

                </div>
                @endforeach
            </div>
            @endforeach

        </section>
        @endif

    </div>

</main>
@endsection
