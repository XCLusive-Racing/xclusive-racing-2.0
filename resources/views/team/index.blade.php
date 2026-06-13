@extends('layouts.app')

@section('title', 'Meet Our Team - ' . config('xcl.name'))

@section('content')
<main class="xcl-page pb-5 px-3" style="background:white">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- Page header --}}
        <div class="pt-4 mb-5">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">MEET OUR TEAM</h1>
            <div class="section-divider" style="margin-left:0"></div>
            <p class="mt-3 mb-0" style="color:#6b7280;font-size:.95rem">The people behind XCLusive Racing — on track and off.</p>
        </div>

        {{-- 3 category cards --}}
        <div class="team-category-grid mb-5">

            @foreach([
                [
                    'slug'  => 'pro',
                    'color' => '#7c3aed',
                    'title' => 'Professional Drivers',
                    'desc'  => 'Our real-world racing drivers competing at the highest level of motorsport, proudly flying the XCLusive flag.',
                    'image' => '/images/team/Looping by cartech.png',
                    'logo'  => '/images/home/brand/xclusive_racing_logo.png',
                ],
                [
                    'slug'  => 'esports',
                    'color' => '#c084fc',
                    'title' => 'Esports Drivers',
                    'desc'  => 'Elite sim racers representing XCLusive across ACC, LMU, iRacing and more — chasing every tenth.',
                    'image' => '/images/team/XCLusive Placeholder lmu.png',
                    'logo'  => '/images/team/xclusive_esports_logo_text.png',
                ],
                [
                    'slug'  => 'staff',
                    'color' => '#3b82f6',
                    'title' => 'Staff',
                    'desc'  => 'The team behind the team — organizers, stewards, and community managers keeping it all running.',
                    'image' => '/images/team/XCLusive Placeholder.png',
                    'logo'  => '/images/team/XCLusive_Gaming_Events_Logo.png',
                ],
            ] as $cat)

            <div class="events-platform-card"
                 x-data="{ on: false }"
                 @mouseenter="on = true"
                 @mouseleave="on = false"
                 :class="on ? 'events-platform-card--active' : ''">

                {{-- Background: swap $cat['image'] src once images are ready --}}
                @if($cat['image'])
                    <img src="{{ $cat['image'] }}" alt="{{ $cat['title'] }}" class="events-platform-card__video">
                @else
                    <div style="position:absolute;inset:0;background:linear-gradient(160deg,#1a0a2e 0%,{{ $cat['color'] }}55 100%)"></div>
                @endif

                <div class="events-platform-card__gradient" style="background:linear-gradient(160deg,{{ $cat['color'] }}{{ $cat['image'] ? '18' : '44' }} 0%,{{ $cat['color'] }}{{ $cat['image'] ? '44' : 'bb' }} 100%)"></div>
                <div class="events-platform-card__shadow"></div>
                <div class="events-platform-card__top-bar" style="background:{{ $cat['color'] }}"></div>

                {{-- Category logo top-left --}}
                @if($cat['logo'])
                <div class="events-platform-card__logo">
                    <img src="{{ $cat['logo'] }}" alt="{{ $cat['title'] }}" height="33">
                </div>
                @endif

                {{-- Body --}}
                <div class="events-platform-card__body">
                    <div class="events-platform-card__title">{{ $cat['title'] }}</div>
                    <div class="events-platform-card__desc" :class="on ? 'events-platform-card__desc--visible' : ''">
                        <p>{{ $cat['desc'] }}</p>
                        <span class="events-platform-card__cta" style="background:{{ $cat['color'] }}">
                            View Drivers →
                        </span>
                    </div>
                </div>

            </div>
            @endforeach

        </div>

    </div>
</main>
@endsection
