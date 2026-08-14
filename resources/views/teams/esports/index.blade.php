@extends('layouts.app')

@section('title', 'Esports Drivers — ' . config('xcl.name'))

@section('content')
<main class="pro-listing-page">

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
            <p class="pro-eyebrow">XCL ESPORTS PROGRAMME</p>
            <h1 class="pro-listing-title">ESPORTS<br><span class="pro-listing-title--lime">DRIVERS</span></h1>
            <div class="section-divider mb-4" style="margin-left:0"></div>
            <p class="pro-listing-sub">
                Elite sim racers representing XCLusive across ACC, LMU and iRacing — chasing every tenth.
            </p>
        </div>
    </section>

    {{-- Platform tabs + roster --}}
    <section class="position-relative" style="z-index:1;padding-bottom:4rem">

        @php
            $socialIconClasses = [
                'twitter'   => 'fa-brands fa-x-twitter',
                'instagram' => 'fa-brands fa-instagram',
                'website'   => 'fa-solid fa-globe',
                'linkedin'  => 'fa-brands fa-linkedin',
                'facebook'  => 'fa-brands fa-facebook',
                'twitch'    => 'fa-brands fa-twitch',
                'tiktok'    => 'fa-brands fa-tiktok',
                'youtube'   => 'fa-brands fa-youtube',
            ];
        @endphp

        <div class="container-xl px-4" data-tabs data-default-tab="acc">

            {{-- Tabs --}}
            <div class="esports-tabs">
                @foreach(['acc' => 'ACC · Console', 'lmu' => 'LMU · PC', 'iracing' => 'iRacing · PC'] as $key => $label)
                <button class="esports-tab"
                        data-tab-btn="{{ $key }}"
                        data-tab-active-class="esports-tab--active">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- ACC --}}
            <div data-tab-panel="acc" style="display:none">
                <div class="esports-grid">
                    @foreach($drivers['acc'] as $d)
                    <div class="esports-driver-card">
                        <div class="esports-driver-card__portrait {{ $d['photo'] ? '' : 'esports-driver-card__portrait--blank' }}">
                            @if($d['photo'])
                            <img src="{{ $d['photo'] }}" alt="{{ $d['name'] }}">
                            @endif
                            @if($d['flag'])
                            <img src="/images/flags/flag-{{ $d['flag'] }}.png"
                                 alt="" class="esports-driver-card__flag">
                            @endif
                            <a href="{{ route('teams.esports.show', $d) }}" class="esports-driver-card__profile-link">View Profile →</a>
                            @if(!empty($d['socials']))
                            <div class="esports-driver-card__socials">
                                @foreach($d['socials'] as $s)
                                <a href="{{ $s['href'] }}" class="esports-driver-card__social-link" title="{{ $s['type'] }}" target="_blank" rel="noopener noreferrer">
                                    <i class="{{ $socialIconClasses[$s['type']] ?? 'fa-solid fa-link' }}"></i>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="esports-driver-card__name">{{ $d['name'] }}</div>
                        <div class="esports-driver-card__role">ACC · Console</div>
                    </div>
                    @endforeach
                </div>

                <x-team-upcoming-events :events="$upcomingEventsByGame['acc']" :team-drivers="$drivers['acc']" :limit="2" />
            </div>

            {{-- LMU --}}
            <div data-tab-panel="lmu" style="display:none">
                <div class="esports-grid">
                    @foreach($drivers['lmu'] as $d)
                    <div class="esports-driver-card">
                        <div class="esports-driver-card__portrait {{ $d['photo'] ? '' : 'esports-driver-card__portrait--blank' }}">
                            @if($d['photo'])
                            <img src="{{ $d['photo'] }}" alt="{{ $d['name'] }}">
                            @endif
                            @if($d['flag'])
                            <img src="/images/flags/flag-{{ $d['flag'] }}.png"
                                 alt="" class="esports-driver-card__flag">
                            @endif
                            <a href="{{ route('teams.esports.show', $d) }}" class="esports-driver-card__profile-link">View Profile →</a>
                            @if(!empty($d['socials']))
                            <div class="esports-driver-card__socials">
                                @foreach($d['socials'] as $s)
                                <a href="{{ $s['href'] }}" class="esports-driver-card__social-link" title="{{ $s['type'] }}" target="_blank" rel="noopener noreferrer">
                                    <i class="{{ $socialIconClasses[$s['type']] ?? 'fa-solid fa-link' }}"></i>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="esports-driver-card__name">{{ $d['name'] }}</div>
                        <div class="esports-driver-card__role">LMU · PC</div>
                    </div>
                    @endforeach
                </div>

                <x-team-upcoming-events :events="$upcomingEventsByGame['lmu']" :team-drivers="$drivers['lmu']" :limit="2" />
            </div>

            {{-- iRacing --}}
            <div data-tab-panel="iracing" style="display:none">
                <div class="esports-grid">
                    @foreach($drivers['iracing'] as $d)
                    <div class="esports-driver-card">
                        <div class="esports-driver-card__portrait {{ $d['photo'] ? '' : 'esports-driver-card__portrait--blank' }}">
                            @if($d['photo'])
                            <img src="{{ $d['photo'] }}" alt="{{ $d['name'] }}">
                            @endif
                            @if($d['flag'])
                            <img src="/images/flags/flag-{{ $d['flag'] }}.png"
                                 alt="" class="esports-driver-card__flag">
                            @endif
                            <a href="{{ route('teams.esports.show', $d) }}" class="esports-driver-card__profile-link">View Profile →</a>
                            @if(!empty($d['socials']))
                            <div class="esports-driver-card__socials">
                                @foreach($d['socials'] as $s)
                                <a href="{{ $s['href'] }}" class="esports-driver-card__social-link" title="{{ $s['type'] }}" target="_blank" rel="noopener noreferrer">
                                    <i class="{{ $socialIconClasses[$s['type']] ?? 'fa-solid fa-link' }}"></i>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="esports-driver-card__name">{{ $d['name'] }}</div>
                        <div class="esports-driver-card__role">iRacing · PC</div>
                    </div>
                    @endforeach
                </div>

                <x-team-upcoming-events :events="$upcomingEventsByGame['iracing']" :team-drivers="$drivers['iracing']" :limit="2" />
            </div>

        </div>
    </section>

</main>
@endsection
