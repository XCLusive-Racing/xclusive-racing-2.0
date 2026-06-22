@extends('layouts.app')

@section('title', 'Professional Drivers — ' . config('xcl.name'))

@section('content')
<main class="pro-listing-page">

    {{-- Topo texture --}}
    <div class="pro-topo" style="background-image:url('/topo.png')"></div>

    {{-- Header --}}
    <section class="pro-listing-header position-relative" style="z-index:1">
        <div class="container-xl px-4">
            <a href="{{ route('team') }}" class="pro-back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Team
            </a>
            <p class="pro-eyebrow">XCL RACING PROGRAMME</p>
            <h1 class="pro-listing-title">PROFESSIONAL<br><span class="pro-listing-title--lime">DRIVERS</span></h1>
            <div class="section-divider mb-4" style="margin-left:0"></div>
            <p class="pro-listing-sub">
                Our real-world motorsport drivers carrying the XCLusive flag on track across Europe's most prestigious championships.
            </p>
        </div>
    </section>

    {{-- Driver grid --}}
    <section class="pro-listing-grid-section position-relative" style="z-index:1">
        <div class="container-xl px-4">
            <div class="pro-listing-grid">
                @foreach($drivers as $slug => $driver)
                <a href="{{ route('teams.pro.show', $slug) }}" class="pro-listing-card">

                    {{-- Portrait --}}
                    <div class="pro-listing-card__img">
                        <img src="{{ $driver['portrait'] }}" alt="{{ $driver['name'] }}" class="pro-listing-card__portrait">
                        <div class="pro-listing-card__gradient"></div>

                        {{-- Flag --}}
                        <div class="pro-listing-card__flag">
                            <img src="/images/flags/flag-{{ $driver['flag'] }}.png" alt="{{ $driver['nationality'] }}">
                        </div>
                    </div>

                    {{-- Bottom info --}}
                    <div class="pro-listing-card__body">
                        <div class="pro-listing-card__name">{{ $driver['name'] }}</div>
                        <div class="pro-listing-card__role">Professional Driver</div>
                        <span class="pro-listing-card__cta">VIEW PROFILE →</span>
                    </div>

                </a>
                @endforeach
            </div>
        </div>
    </section>

</main>
@endsection
