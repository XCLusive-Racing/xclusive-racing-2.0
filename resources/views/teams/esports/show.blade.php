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

    </div>

</main>
@endsection
