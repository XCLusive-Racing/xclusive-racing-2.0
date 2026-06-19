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
@endphp

<main class="pro-driver-page">

    {{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
    <section class="pro-driver-hero"
             @if($driver['hero'])
             style="background-image:url('{{ $driver['hero'] }}')"
             @endif>

        <div class="pro-driver-hero__overlay"></div>

        {{-- Portrait shown when no landscape hero is uploaded --}}
        @unless($driver['hero'])
        <div class="pro-driver-hero__portrait-wrap">
            <img src="{{ $driver['portrait'] }}" alt="{{ $driver['name'] }}" class="pro-driver-hero__portrait">
        </div>
        @endunless

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
                 x-data="{ year: {{ $latestYear }} }">

            <div class="pro-driver-results__header">
                <div class="pro-section-label">RACE RESULTS</div>

                {{-- Year tabs --}}
                <div class="pro-year-tabs">
                    @foreach(array_keys($driver['results']) as $y)
                    <button class="pro-year-tab"
                            :class="year === {{ $y }} ? 'pro-year-tab--active' : ''"
                            @click="year = {{ $y }}">
                        {{ $y }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Per-year content --}}
            @foreach($driver['results'] as $year => $championships)
            <div x-show="year === {{ $year }}" x-cloak>

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

                    </div>
                    @endforeach
                @endif

            </div>
            @endforeach

        </section>

    </div>

</main>
@endsection
