@extends('layouts.app')

@section('title', 'Race & Events - XCLusive Racing')

@section('content')
<main class="xcl-page pb-5 px-3 bg-light" style="position:relative;overflow:hidden" x-data="{
    platform: null,
    weeksShown: 1,
    eventFilter: 'all',
    selectPlatform(p) { this.platform = p; this.weeksShown = 1; this.eventFilter = 'all'; },
    matchesEventFilter(tag, dateStr) {
        const now    = new Date();
        const d      = new Date(dateStr);
        const cutoff = new Date(now);
        cutoff.setDate(cutoff.getDate() + this.weeksShown * 7);
        if (this.eventFilter === 'all') return d >= now && d <= cutoff;
        return tag === this.eventFilter && d >= now && d <= cutoff;
    }
}">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>
    <div class="container-xl" style="position:relative;z-index:1">
        <div class="mb-5 pt-4">
            <h1 class="display-4 fw-black text-uppercase fst-italic text-dark mb-2">RACE &amp; EVENTS</h1>
        </div>

        {{-- Platform cards --}}
        <div x-show="platform === null">
            <div class="d-flex gap-3 mb-5 align-items-end" style="height:460px">
                @foreach([
                    ['acc',     '#7c3aed', 'ACC Console',      'Assetto Corsa Competizione · PS5 &amp; Xbox Series X/S'],
                    ['lmu',     '#db2877', 'Le Mans Ultimate',  'Le Mans Ultimate · Premium PC Sim Racing'],
                    ['iracing', '#2563eb', 'iRacing',           'iRacing · World\'s Leading Online Sim Racing'],
                    ['ac',      '#16a34a', 'AC Rally',          'Assetto Corsa Rally · PC Sim Racing'],
                ] as [$game, $color, $label, $desc])
                @php $count = $races->where('game', $game)->where('status', 'open')->count(); @endphp
                <div x-data="{ on: false }"
                     @mouseenter="on = true;  $refs.vid.play().catch(()=>{})"
                     @mouseleave="on = false; $refs.vid.pause()"
                     @click="selectPlatform('{{ $game }}')"
                     :style="{ height: on ? '460px' : '280px' }"
                     style="flex:1;height:280px;border-radius:16px;overflow:hidden;cursor:pointer;position:relative;transition:height .45s cubic-bezier(.4,0,.2,1)">

                    <video x-ref="vid" muted loop playsinline preload="none"
                           style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                        <source src="/videos/{{ $game }}.mp4" type="video/mp4">
                    </video>

                    <div style="position:absolute;inset:0;background:linear-gradient(160deg,{{ $color }}55 0%,{{ $color }}cc 100%)"></div>
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.25) 55%,transparent 100%)"></div>

                    <div style="position:absolute;bottom:0;left:0;right:0;padding:1.5rem">
                        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:{{ $color }};background:rgba(0,0,0,.35);display:inline-block;padding:3px 10px;border-radius:20px;margin-bottom:.6rem">
                            {{ $count }} open {{ $count === 1 ? 'event' : 'events' }}
                        </div>
                        <div style="color:white;font-size:1.45rem;font-weight:900;text-transform:uppercase;font-style:italic;line-height:1.1;margin-bottom:.75rem">
                            {!! $label !!}
                        </div>
                        <div :style="on ? 'max-height:120px;opacity:1' : 'max-height:0;opacity:0'"
                             style="overflow:hidden;transition:max-height .35s ease,opacity .3s ease">
                            <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin-bottom:.85rem">{!! $desc !!}</p>
                            <span style="background:{{ $color }};color:white;padding:8px 22px;border-radius:8px;font-weight:800;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;display:inline-block">
                                View Events →
                            </span>
                        </div>
                    </div>

                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{ $color }}"></div>
                </div>
                @endforeach
            </div>

            @guest
            <div class="rounded-3 p-5 text-white text-center bg-gradient-xcl">
                <h2 class="fs-2 fw-black text-uppercase fst-italic mb-3">READY TO RACE?</h2>
                <p class="mb-4 fs-5">Sign up now to access all events and track your ELO rating</p>
                <a href="{{ route('register') }}" class="btn btn-light fw-black text-uppercase px-4 py-2 text-xcl-purple">
                    CREATE PROFILE
                </a>
            </div>
            @endguest
        </div>

        {{-- Platform selected: show races --}}
        <div x-show="platform !== null" x-cloak>
            <button @click="platform = null"
                class="btn btn-link fw-bold text-uppercase text-xcl-purple text-decoration-none mb-4 ps-0">
                ← BACK TO PLATFORMS
            </button>


            {{-- Event type filter (dynamic tags) --}}
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
                    <div class="rounded-3 p-5 text-center bg-white shadow-sm">
                        <h3 class="fs-1 fw-black text-uppercase fst-italic text-dark mb-3">NO UPCOMING EVENTS</h3>
                        <p class="text-secondary fs-5">Check back soon for new events!</p>
                    </div>
                @else
                    <div class="row row-cols-3 g-3">
                        @foreach($gameRaces as $race)
                        @php
                            $titleLower = strtolower($race->title ?? '');
                            if ($race->is_championship) {
                                $badge   = 'SR5 GRID';
                                $overlay = 'rgba(123,47,190,0.55)';
                            } elseif (str_contains($titleLower, 'multiclass') || str_contains($titleLower, 'endurance')) {
                                $badge   = 'MULTICLASS';
                                $overlay = 'rgba(0,210,120,0.45)';
                            } else {
                                $badge   = 'DAILY SPRINT';
                                $overlay = 'rgba(0,180,160,0.45)';
                            }
                            $gameShort = match($race->game) {
                                'acc'     => 'ACC',
                                'lmu'     => 'LMU',
                                'iracing' => 'iRACING',
                                'ac'      => 'AC RALLY',
                                default   => strtoupper($race->game),
                            };
                            $platforms = match($race->game) {
                                'acc'     => [['fa-brands fa-playstation', 'PS5'], ['fa-brands fa-xbox', 'Xbox']],
                                'lmu'     => [['fa-solid fa-desktop', 'PC']],
                                'iracing' => [['fa-solid fa-desktop', 'PC']],
                                'ac'      => [['fa-solid fa-desktop', 'PC']],
                                default   => [],
                            };
                        @endphp
                        <div class="col"
                             x-show="matchesEventFilter('{{ $race->event_tag ?? 'daily' }}', '{{ $race->scheduled_at->toIso8601String() }}')">
                            <div class="xcl-ec2">

                                {{-- Image 16:9 --}}
                                <div class="xcl-ec2__img-wrap">
                                    @if($race->image)
                                        <img src="{{ asset('storage/'.$race->image) }}"
                                             alt="{{ $race->title }}" loading="lazy"
                                             class="xcl-ec2__img">
                                    @else
                                        <div class="xcl-ec2__img-placeholder"></div>
                                    @endif

                                    <div class="xcl-ec2__overlay" style="background:{{ $overlay }}"></div>

                                    <div class="xcl-ec2__badge-wrap">
                                        <div class="xcl-ec2__badge">
                                            <div class="xcl-ec2__badge-main">{{ $badge }}</div>
                                            <div class="xcl-ec2__badge-sub">{{ $gameShort }}</div>
                                        </div>
                                    </div>

                                    <div class="xcl-ec2__platforms">
                                        @foreach($platforms as [$icon, $label])
                                            <i class="{{ $icon }}" title="{{ $label }}"></i>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Info --}}
                                <div class="xcl-ec2__body">
                                    <div class="xcl-ec2__time">
                                        {{ strtoupper($race->scheduledAtUk()->format('l')) }} /
                                        {{ strtoupper($race->scheduledAtUk()->format('g:i A T')) }}
                                    </div>
                                    <div class="xcl-ec2__meta">
                                        {{ $race->scheduledAtUk()->format('D, M d') }}
                                        @if($race->track) | {{ $race->track }} @endif
                                    </div>
                                    <a href="{{ route('race.show', $race) }}" class="xcl-see-event-btn">
                                        SEE EVENT
                                    </a>
                                </div>

                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-5">
                        <button x-show="weeksShown === 1" @click="weeksShown = 2"
                                class="btn fw-black text-uppercase px-5 py-2"
                                style="background:rgba(124,58,237,.15);color:#a855f7;border:1.5px solid rgba(168,85,247,.3);border-radius:8px;font-size:.85rem">
                            Load more
                        </button>
                        <button x-show="weeksShown === 2" @click="weeksShown = 1"
                                class="btn fw-black text-uppercase px-5 py-2"
                                style="background:rgba(124,58,237,.15);color:#a855f7;border:1.5px solid rgba(168,85,247,.3);border-radius:8px;font-size:.85rem">
                            Load less
                        </button>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</main>
@endsection